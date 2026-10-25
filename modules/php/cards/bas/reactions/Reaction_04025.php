<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhasePlanningEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04025 extends CardReaction
{
    // WHY: chosenCard is a shared slot. Cat's Embargo writes a single card id into it
    // during planningPhaseEnd_01098_2, which can run after Look and before this pick.
    // json_decode of that id is the integer itself, and count() then fatals.
    public const LOOKED_CARDS = 'reaction04025LookedCards';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Look at top of deck; draw two; sink the rest");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may look at the top of your deck (draw two, sink the rest): ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Look'), 'look');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    /**
     * Merchants the scheme controller has in play (Home + City).
     *
     * @return int
     */
    private function countControlledMerchants(Theah $theah, int $controllerId): int
    {
        $count = 0;
        foreach ($theah->getCharactersInPlayByPlayerId($controllerId) as $character)
        {
            if ($character->hasTrait("Merchant"))
            {
                $count++;
            }
        }

        return $count;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventPhasePlanningEnd && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner == null)
            {
                return;
            }

            // Chosen schemes sit at Home until Dusk locker (_01098 / _03041).
            if ($owner->Location != Game::LOCATION_PLAYER_HOME)
            {
                return;
            }

            // WHY: Merchant Reaction — trait gate when no pickable performer in the trigger.
            $merchantCount = $this->countControlledMerchants($event->theah, $owner->ControllerId);
            if ($merchantCount < 1)
            {
                return;
            }

            $lookCount = 3 + $merchantCount;
            $deckCards = $event->theah->game->getCardsOnTopOfPlayerFactionDeck($owner->ControllerId, $lookCount);
            if (count($deckCards) == 0)
            {
                return;
            }

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);

        if ($reactionId == 'pass')
        {
            // Pass does not consume setUsed — EventPhasePlanningEnd will not re-fire today.
            $game->gamestate->nextState("done");
            return;
        }

        if ($reactionId == 'look')
        {
            $merchantCount = $this->countControlledMerchants($game->theah, $owner->ControllerId);
            $cards = $this->peekFactionDeck($game, $owner->ControllerId);

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} looks at the top ${count} cards of their deck (${merchant_count} Merchant(s)).'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "count" => count($cards),
                "merchant_count" => $merchantCount,
            ]);

            $this->setUsed($game->theah, true);
            $owner->IsUpdated = true;

            if (count($cards) == 0)
            {
                $game->gamestate->nextState("done");
                return;
            }

            // WHY: Cannot draw two of fewer than three — auto-draw all looked cards, nothing to sink.
            if (count($cards) <= 2)
            {
                $drawIds = array_map(fn($c) => (int) $c['id'], $cards);
                $this->drawIdsAndSinkRest($game, $owner->ControllerId, $drawIds, $cards);
                $game->gamestate->nextState("done");
                return;
            }

            $this->rememberLookedCards($game, $cards);

            // WHY: 4th arg = reaction Id so actFromCardWithIds routes to this reaction (03052).
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04025", $this->Id);
            $game->theah->queueEvent($transition);
        }

        $game->gamestate->nextState("done");
    }

    public function getArgsFromReaction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromReaction($game, $state, $stateName);

        if ($state == States::PLANNING_PHASE_END_04025)
        {
            $cards = $this->lookedCards($game);
            $args['cards'] = $cards;
            $args['cardsToDraw'] = min(2, count($cards));
        }

        return $args;
    }

    public function actFromReactionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromReactionWithIds($game, $state, $stateName, $ids);

        if ($state == States::PLANNING_PHASE_END_04025)
        {
            $owner = $this->getOwningCard($game->theah);
            $originalCards = $this->lookedCards($game);
            $originalIds = array_map(fn($card) => (int) (is_array($card) ? $card['id'] : $card->id), $originalCards);
            $drawCount = min(2, count($originalIds));

            if (count($ids) != $drawCount)
            {
                throw new UserException(sprintf($game->translate("You must choose exactly %d card(s) to draw."), $drawCount));
            }

            foreach ($ids as $id)
            {
                if (! in_array((int) $id, $originalIds, true))
                {
                    throw new UserException($game->translate("Card is not among the looked-at cards."));
                }
            }

            $this->drawIdsAndSinkRest($game, $owner->ControllerId, array_map('intval', $ids), $originalCards);

            $game->gamestate->nextState();
        }
    }

    /**
     * Looked-at cards for the pick state. Ignores chosenCard: that global may hold
     * Cat's Embargo's revealed card id (an int) while this state is waiting.
     *
     * @return array<int, object>
     */
    public function lookedCards(Game $game): array
    {
        $stored = self::decodeCardList($game->globals->get(self::LOOKED_CARDS));
        if ($stored !== [])
        {
            return $stored;
        }

        $owner = $this->getOwningCard($game->theah);
        if ($owner === null)
        {
            return [];
        }

        // The pick has not moved these cards yet, so the top of the deck is still the look.
        $cards = $this->peekFactionDeck($game, $owner->ControllerId);
        if ($cards === [])
        {
            return [];
        }

        return $this->rememberLookedCards($game, $cards);
    }

    /**
     * @return array<int, object>
     */
    public static function decodeCardList(mixed $raw): array
    {
        if (is_array($raw))
        {
            $raw = json_encode($raw);
        }
        if (! is_string($raw) || $raw === '')
        {
            return [];
        }

        $decoded = json_decode($raw);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function peekFactionDeck(Game $game, int $controllerId): array
    {
        $merchantCount = $this->countControlledMerchants($game->theah, $controllerId);
        $lookCount = 3 + $merchantCount;
        $deckCards = $game->getCardsOnTopOfPlayerFactionDeck($controllerId, $lookCount);
        $cards = [];
        foreach ($deckCards as $deckCard)
        {
            $card = $game->getCardObjectFromDb($deckCard['id']);
            if ($card === null)
            {
                continue;
            }
            $cards[] = $card->getPropertyArray($game);
        }

        return $cards;
    }

    /**
     * @param array<int, array<string, mixed>> $cards
     * @return array<int, object>
     */
    private function rememberLookedCards(Game $game, array $cards): array
    {
        $encoded = json_encode($cards);
        $game->globals->set(self::LOOKED_CARDS, $encoded);

        return self::decodeCardList($encoded);
    }

    /**
     * @param array<int> $drawIds
     * @param array<object>|array<int, array<string, mixed>> $lookedCards
     */
    private function drawIdsAndSinkRest(Game $game, int $playerId, array $drawIds, array $lookedCards): void
    {
        $deckName = $game->getPlayerFactionDeckName($playerId);
        $drawIdSet = array_flip($drawIds);

        foreach ($lookedCards as $looked)
        {
            $id = (int) (is_array($looked) ? $looked['id'] : $looked->id);
            $card = $game->getCardObjectFromDb($id);
            if ($card === null || $card->Location != $deckName)
            {
                throw new UserException($game->translate("Card is not in your Faction Deck."));
            }

            if (isset($drawIdSet[$id]))
            {
                // WHY: Signal remove + hand add — physical move is in EventCardAddedToHand (Otto 01038).
                $removeEvent = EventFactory::createCardRemovedFromPlayerFactionDeckEvent($playerId, $id);
                $game->theah->eventCheck($removeEvent);

                $addEvent = EventFactory::createCardAddedToHandEvent($playerId, $id);
                $game->theah->eventCheck($addEvent);

                $game->theah->queueEvent($removeEvent);
                $game->theah->queueEvent($addEvent);
            }
            else
            {
                // WHY: Sink = bottom of faction deck, not discard (Otto / Yevgeni).
                $sinkEvent = EventFactory::createCardAddedToFactionDeckEvent($playerId, $id, false);
                $game->theah->queueEvent($sinkEvent);
            }
        }

        $owner = $this->getOwningCard($game->theah);
        $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} draws ${draw_count} card(s) and sinks the rest.'), [
            "card_inject_code" => $owner->getInjectCode(),
            "player_name" => $game->getPlayerNameById($playerId),
            "draw_count" => count($drawIds),
        ]);
    }
}
