<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © Edward Mittelstedt bugbucket@comcast.net
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

 namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;

trait DeckTrait
{
    /** @var array<int> Player ids awaiting a batched faction-deck count notif */
    private array $factionDeckCountBatch = [];

    /** @var int Nesting depth — planning draws can reshuffle inside an outer batch */
    private int $factionDeckCountBatchDepth = 0;

    /**
     * Push the absolute faction-deck size to all clients.
     * WHY: Absolute count (not delta) so any path can refresh safely; batching
     * collapses N moves (discard reshuffle / panache draw) into one notif.
     */
    public function notifyFactionDeckCount(int $playerId): void
    {
        if ($this->factionDeckCountBatchDepth > 0)
        {
            $this->factionDeckCountBatch[] = $playerId;
            return;
        }

        $this->notifyFactionDeckCountNow($playerId);
    }

    private function notifyFactionDeckCountNow(int $playerId): void
    {
        $deckCount = (int) $this->cards->countCardsInLocation($this->getPlayerFactionDeckName($playerId));
        $this->notify->all("factionDeckCount", '', [
            "playerId" => $playerId,
            "deckCount" => $deckCount,
        ]);
    }

    public function beginFactionDeckCountBatch(): void
    {
        if ($this->factionDeckCountBatchDepth === 0)
        {
            $this->factionDeckCountBatch = [];
        }
        $this->factionDeckCountBatchDepth++;
    }

    public function endFactionDeckCountBatch(): void
    {
        if ($this->factionDeckCountBatchDepth === 0)
        {
            return;
        }

        $this->factionDeckCountBatchDepth--;
        if ($this->factionDeckCountBatchDepth > 0)
        {
            return;
        }

        $playerIds = array_unique($this->factionDeckCountBatch);
        $this->factionDeckCountBatch = [];
        foreach ($playerIds as $playerId)
        {
            $this->notifyFactionDeckCountNow((int) $playerId);
        }
    }

    private function maybeNotifyFactionDeckCounts(string $oldLocation, string $newLocation): void
    {
        $playerIds = [];
        if (str_starts_with($oldLocation, 'Faction-'))
        {
            $playerIds[] = (int) substr($oldLocation, strlen('Faction-'));
        }
        if (str_starts_with($newLocation, 'Faction-'))
        {
            $playerIds[] = (int) substr($newLocation, strlen('Faction-'));
        }
        foreach (array_unique($playerIds) as $playerId)
        {
            if ($playerId > 0)
            {
                $this->notifyFactionDeckCount($playerId);
            }
        }
    }

    /**
     * Insert on top/bottom of a player's faction deck and refresh the deck-count UI.
     * Does not sync Card->Location — callers that need that still set it themselves
     * (see Reaction_03006 / gamble confirm notes about Location lag).
     */
    public function insertCardOnPlayerFactionDeckExtreme(int $cardId, int $playerId, bool $onTop): void
    {
        $deckName = $this->getPlayerFactionDeckName($playerId);
        $this->cards->insertCardOnExtremePosition($cardId, $deckName, $onTop);
        $this->notifyFactionDeckCount($playerId);
    }

    public function buildDecks() {

        // *** Create the city deck ***

        // Load the city deck JSON
        $city_decks = json_decode(CityDecks::$decks);
        $cityDeckChoice = (int) $this->tableOptions->get(Game::OPTIONS_CITY_DECK);
        $cityDeck = $city_decks->decks[$cityDeckChoice];

        // WHY: Record when the chosen build is materialized (same moment as deck creation),
        // not at end-of-game — option is fixed for the table once decks are built.
        $this->bga->tableStats->set(Game::STAT_CITY_DECK, $cityDeckChoice);

        foreach ($cityDeck->cards as $cityCard)
            $card = $this->createCardInLocation($cityCard, Game::LOCATION_CITY_DECK, 0, 0);
        
        $this->cards->shuffle(Game::LOCATION_CITY_DECK);

        // Load the decks selected by the players
        $players = $this->loadPlayersBasicInfos();
        foreach ( $players as $playerId => $player ) 
        {
            // Get the source and deck_id of the deck from the DB for the  player
            $result = $this->getObjectFromDB("SELECT deck_source FROM player WHERE player_id = '$playerId'");
            $deck = json_decode($result['deck_source']);
            
            //Now that we have a deck, add the cards in the deck to the db

            $faction = $deck->faction;

            // Leader
            $card = $this->createCardInLocation($deck->leader, Game::LOCATION_PLAYER_HOME, $playerId, $playerId);

            //The Leader's faction is the same as the deck's faction.  This will override any original factions of the leader card.
            $card->initializeFaction($faction);
            $this->updateCardObjectInDb($card);

            //Set the id of the leader card in the player record
            $sql = "UPDATE player SET leader_card_id = $card->Id WHERE player_id = $playerId";
            $this->DbQuery($sql);

            // WHY: playLeader fires before faction cards are inserted; compute from
            // the deck definition so the home deck-count icon is correct immediately.
            $deckCount = 0;
            foreach ($deck->faction_deck as $factionCard) {
                $deckCount += $factionCard->count;
            }

            //Notify players about the leaders
            $this->notifyAllPlayers("playLeader", clienttranslate('${player_name} is playing <strong>${player_faction} Faction</strong> and ${leader_inject_code} as their leader.'), [
                "player_name" => $player['player_name'],
                "player_faction" => $faction,
                "leader_inject_code" => $card->getInjectCode(),
                "player_id" => $playerId,
                "player_color" => $player['player_color'],
                "leader" => $card->getPropertyArray($this),
                "deckCount" => $deckCount,
            ]);

            // WHY: Set when decks materialize (covers manual, random, and tournament), not at pick time.
            $leaderStatIndex = array_search($card->Name, Game::LEADER_STAT_LABELS, true);
            $this->bga->playerStats->set(Game::STAT_LEADER, $leaderStatIndex === false ? 0 : $leaderStatIndex, $playerId);

            // *** Create the approach deck and send each card to the player ***
            $approachDeck = $deck->approach_deck;
            $cards = [];
            foreach ($approachDeck as $approachCard) {
                $card = $this->createCardInLocation($approachCard, Game::LOCATION_APPROACH, $playerId, $playerId);
                $cards[] = $card->getPropertyArray($this);
            }

            $cardList = implode(", ", array_map(function($card) { return clienttranslate($card['name']); }, $cards));
            $this->notifyPlayer($playerId, "approachCardsReceived", 
                clienttranslate('Private:You received your Approach Deck containing: ${card_list}'), [
                    "card_list" => $cardList,
                    "cards" => $cards
                ]);

            // Create player's Faction deck
            $factionDeck = $deck->faction_deck;
            $cards = [];
            $location = $this->getPlayerFactionDeckName($playerId);
            foreach ($factionDeck as $factionCard) {
                for ($i = 0; $i < $factionCard->count; $i++) 
                {
                    $card = $this->createCardInLocation($factionCard->id, $location, $playerId, $playerId);
                    $cards[] = $card->getPropertyArray($this);
                }
            }
            $this->cards->shuffle($location, $playerId);
        }
    }

    public function getCardPropertiesInLocation($location, $playerId = null)
    {
        $cards = [];
        $locationCards = $this->cards->getCardsInLocation($location);
        foreach ($locationCards as $cardId) {
            $card = $this->getCardObjectFromDb($cardId['id']);
            if ($playerId !== null && $card->ControllerId != $playerId)
            {
                unset($card);
                continue;
            }

            $cards[] = $card->getPropertyArray($this);
            unset($card);
        }
       
        return $cards;
    }

    public function updateCardObjectInDb($card) 
    {
        $serialized = addslashes(serialize($card));
        $sql = "UPDATE card set card_serialized = '{$serialized}' WHERE card_id = $card->Id";
        $this->DbQuery($sql);
    }

    /**
     * Move a card in the deck component and keep Card->Location in sync.
     *
     * WHY: card_location (deck) and the serialized Location property are twin
     * records. Raw $this->cards->moveCard() only updates the former and is the
     * source of "card appears in discard but confirm rejects it" wedges. Prefer
     * this method everywhere a card truly changes its logical location.
     *
     * For temporary parks that must leave Location alone (Purgatory holding a
     * hand card pending discard), or for premature deck moves that a queued
     * event will finish (EventCardMoved / EventCityCardAddedToLocation), use
     * moveCardInDeck() instead.
     *
     * @param int $cardId
     * @param string $location Destination location name
     * @param int|string $locationArg Optional deck location_arg (player id for Hand etc.)
     * @param Card|null $card Optional already-loaded instance to mutate in place
     * @return Card The card with Location updated and persisted
     */
    public function moveCard(int $cardId, string $location, $locationArg = 0, ?Card $card = null): Card
    {
        $before = $this->cards->getCard($cardId);
        $oldLocation = is_array($before) ? (string) ($before['location'] ?? '') : '';

        $this->cards->moveCard($cardId, $location, $locationArg);

        if ($card === null) {
            // Prefer the in-world instance so Theah stays coherent with the DB write.
            $card = $this->theah->getCardById($cardId);
            if ($card === null) {
                $card = $this->getCardObjectFromDb($cardId);
            }
        }

        $card->Location = $location;
        $this->updateCardObjectInDb($card);
        $this->maybeNotifyFactionDeckCounts($oldLocation, $location);
        return $card;
    }

    /**
     * Move only the deck row, leaving Card->Location alone.
     *
     * WHY: Purgatory parks need Location to still report Hand for a subsequent
     * discard-from-hand event. Some callers also move the deck row immediately
     * so same-request location queries don't see the card at the old pile, while
     * a queued event (EventCardMoved, EventCityCardAddedToLocation) will set
     * Location shortly after.
     */
    public function moveCardInDeck(int $cardId, string $location, $locationArg = 0): void
    {
        $before = $this->cards->getCard($cardId);
        $oldLocation = is_array($before) ? (string) ($before['location'] ?? '') : '';
        $this->cards->moveCard($cardId, $location, $locationArg);
        $this->maybeNotifyFactionDeckCounts($oldLocation, $location);
    }

    /**
     * Park a card in Purgatory (or another holding location) without changing
     * Card->Location. Convenience wrapper around moveCardInDeck().
     */
    public function parkCard(int $cardId, string $holdingLocation = Game::LOCATION_PURGATORY, $locationArg = 0): void
    {
        $this->moveCardInDeck($cardId, $holdingLocation, $locationArg);
    }

    public function getGameDeckObject() 
    {
        return $this->cards;
    }

    public function getPlayerFactionDeckName($playerId) 
    {
        return "Faction-$playerId";
    }

    public function getPlayerDiscardDeckName($playerId) 
    {
        return "Discard-$playerId";
    }

    public function getPlayerLockerName($playerId) 
    {
        return "Locker-$playerId";
    }

    public function playerDrawCard($playerId): Card
    {
        // WHY: Nest with shuffle's batch so reshuffle+draw is one factionDeckCount
        // (final size after the pick), not a flash of the full reshuffled deck.
        $this->beginFactionDeckCountBatch();

        $location = $this->getPlayerFactionDeckName($playerId);

        //If faction deck is empty move cards from player discard to faction deck
        if ($this->cards->countCardsInLocation($location) == 0)
        {
            $this->shufflePlayerDiscardIntoPlayerFactionDeck($playerId);
        }

        // WHY: Gamble peeks leave revealed cards in the faction deck until choose.
        // Mid-reveal draws (e.g. Desideria 04003b after Unravel 04010's Sorcerer
        // ability) must not pickCard the peeked tops — that stole the first revealed
        // card into hand and left GAMBLE_REVEAL_COUNT pointing at the wrong set.
        // Skip only while a top-of-deck reveal is in progress for this player
        // (!DUEL_GAMBLED): count stays set until EOR after choose, and bottom reveals
        // (Devil Jonah) are not on the draw edge. Mirror Ivy (02042): if nothing sits
        // under the peek even after reshuffle, take from the reveal and shrink count.
        $skipReveal = 0;
        $revealCount = (int) $this->globals->get(Game::GAMBLE_REVEAL_COUNT, 0);
        $fromBottom = (bool) $this->globals->get(Game::GAMBLE_REVEAL_FROM_BOTTOM, false);
        $alreadyGambled = (bool) $this->globals->get(Game::DUEL_GAMBLED, false);
        if ($revealCount > 0 && ! $fromBottom && ! $alreadyGambled && $this->globals->get(Game::IN_DUEL, false))
        {
            $actor = $this->theah->getDuelRoundActor();
            if ($actor !== null && $actor->ControllerId == $playerId)
            {
                $skipReveal = $revealCount;
            }
        }

        if ($skipReveal > 0)
        {
            $deckCards = array_values($this->getCardsOnTopOfPlayerFactionDeck($playerId, $skipReveal + 1));
            if (count($deckCards) > $skipReveal)
            {
                $cardInfo = $deckCards[$skipReveal];
                $this->cards->moveCard($cardInfo['id'], Game::LOCATION_HAND, $playerId);
            }
            else
            {
                $cardInfo = $this->cards->pickCard($location, $playerId);
                $this->globals->set(Game::GAMBLE_REVEAL_COUNT, max(0, $revealCount - 1));
            }
        }
        else
        {
            $cardInfo = $this->cards->pickCard($location, $playerId);
        }

        $card = $this->getCardObjectFromDb($cardInfo['id']);
        $card->ControllerId = $playerId;
        $card->OwnerId = $playerId;
        $card->Location = Game::LOCATION_HAND;
        $this->updateCardObjectInDb($card);

        // WHY: pickCard/cards->moveCard bypass Game::moveCard, so the faction-deck
        // hook never fires — refresh here after every draw.
        $this->notifyFactionDeckCount($playerId);
        $this->endFactionDeckCountBatch();

        return $card;
    }

   
    public function getCardsOnTopOfCityDeck(int $nbr): Array
    {
        $count = $this->cards->countCardsInLocation(Game::LOCATION_CITY_DECK);
        if ($count < $nbr)
        {
            $cards = $this->cards->getCardsOnTop($count, Game::LOCATION_CITY_DECK);
            $ids = array_map(function($card) { return $card['id']; }, $cards);

            $this->shuffleCityDiscardIntoCityDeck();

            //Stick cards already revealed to the top of the deck
            $ids = array_reverse($ids);
            //Insert the cards at the top of the deck
            foreach ($ids as $id)
            {
                $this->cards->insertCardOnExtremePosition($id, Game::LOCATION_CITY_DECK, true);
            }
        }

        return $this->cards->getCardsOnTop($nbr, Game::LOCATION_CITY_DECK);
    }

    public function shuffleCityDiscardIntoCityDeck()
    {
        while($this->cards->countCardsInLocation(Game::LOCATION_CITY_DISCARD) > 0) 
        {
            $cardInfo = $this->cards->getCardOnTop(Game::LOCATION_CITY_DISCARD);
            $cardId = (int)$cardInfo['id'];
            $card = $this->theah->getCardById($cardId);
            if ($card === null)
            {
                $card = $this->getCardObjectFromDb($cardId);
            }
            // WHY: Same mid-day recycle gap as faction discard reshuffle.
            $card->clearAbilityUsedFlags();
            $this->moveCard($cardId, Game::LOCATION_CITY_DECK, 0, $card);
        }
        $this->cards->shuffle(Game::LOCATION_CITY_DECK);

        $this->notifyAllPlayers("cityDiscardShuffled", clienttranslate('The City Discard Pile has been shuffled into the City Deck.'), []);
    }

    public function getCardsOnTopOfPlayerFactionDeck($playerId, int $nbr): Array
    {
        $location = $this->getPlayerFactionDeckName($playerId);

        //If faction deck is empty move cards from player discard to faction deck
        $count = $this->cards->countCardsInLocation($location);
        if ($count < $nbr)
        {
            $cards = $this->cards->getCardsOnTop($count, $location);
            $ids = array_map(function($card) { return $card['id']; }, $cards);

            $this->shufflePlayerDiscardIntoPlayerFactionDeck($playerId);

            //Stick cards already revealed to the top of the deck
            $ids = array_reverse($ids);
            //Insert the cards at the top of the deck
            foreach ($ids as $id)
            {
                $this->cards->insertCardOnExtremePosition($id, $location, true);
            }
        }

        return $this->cards->getCardsOnTop($nbr, $location);
    }

    public function getCardsOnBottomOfPlayerFactionDeck($playerId, int $nbr): Array
    {
        $location = $this->getPlayerFactionDeckName($playerId);

        // Ensure enough cards exist; reuse top helper to trigger a discard reshuffle if needed
        $count = $this->cards->countCardsInLocation($location);
        if ($count < $nbr)
        {
            $this->getCardsOnTopOfPlayerFactionDeck($playerId, $nbr);
        }

        // Lower card_location_arg = bottom
        $allCards = $this->cards->getCardsInLocation($location, null, "card_location_arg");
        $allCards = array_values($allCards);
        return array_slice($allCards, 0, $nbr);
    }

    public function shufflePlayerDiscardIntoPlayerFactionDeck($playerId)
    {
        $location = $this->getPlayerFactionDeckName($playerId);
        $discardLocation = $this->getPlayerDiscardDeckName($playerId);
        // WHY: Each discard→faction moveCard would otherwise spam factionDeckCount.
        $this->beginFactionDeckCountBatch();
        while($this->cards->countCardsInLocation($discardLocation) > 0)
        {
            $cardInfo = $this->cards->getCardOnTop($discardLocation);
            $cardId = (int)$cardInfo['id'];
            // Prefer in-world instance so a discard-pile card already in Theah is
            // cleared in place; otherwise load from DB before the move persists.
            $card = $this->theah->getCardById($cardId);
            if ($card === null)
            {
                $card = $this->getCardObjectFromDb($cardId);
            }
            // WHY: See Card::clearAbilityUsedFlags — mid-day recycle before dusk.
            $card->clearAbilityUsedFlags();
            $this->moveCard($cardId, $location, 0, $card);
        }
        $this->cards->shuffle($location);
        $this->endFactionDeckCountBatch();

        $this->notifyAllPlayers("playerDiscardShuffled", clienttranslate('The Discard Pile of ${player_name} has been shuffled into their Faction Deck.'), [
            'player_name' => $this->getPlayerNameById($playerId),
            'playerId' => $playerId,
        ]);
    }

    public function createCardInLocation(string $className, string $location, int $ownerId, int $controllerId): Card
    {
        $slashedLocation = addslashes($location);
        $sql = "INSERT INTO card (card_type, card_type_arg, card_location, card_location_arg) VALUES ('{$className}', $controllerId, '{$slashedLocation}', $controllerId)";
        $this->DbQuery($sql);
        $id = $this->DbGetLastId();
        
        $card = $this->instantiateCard($className, $id);
        $card->OwnerId = $ownerId;
        $card->ControllerId = $controllerId;
        $card->Location = $location;
        $this->updateCardObjectInDb($card);

        return $card;
    }
}
