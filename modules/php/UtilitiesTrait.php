<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use ArrayAccess;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;

trait UtilitiesTrait
{
    function dbGetAuxScore($player_id) 
    {
        return $this->getUniqueValueFromDB("SELECT player_score_aux FROM player WHERE player_id = $player_id");
    }

    function dbSetAuxScore($player_id, $score) 
    {
        $this->DbQuery("UPDATE player SET player_score_aux = $score WHERE player_id = $player_id");
    }

    public function getAttachmentsInHand(int $playerId)
    {
        $hand = $this->cards->getCardsInLocation(Game::LOCATION_HAND, $playerId);
        $attachments = [];
        foreach ($hand as $handCard) {
            $card = $this->getCardObjectFromDb($handCard['id']);
            if ($card instanceof Attachment) {
                $attachments[] = $card;
            }
        }
        return $attachments;
    }

    public function getManeuversInHand(int $playerId)
    {
        $hand = $this->cards->getCardsInLocation(Game::LOCATION_HAND, $playerId);
        $maneuvers = [];
        foreach ($hand as $handCard) {
            $card = $this->getCardObjectFromDb($handCard['id']);
            if ($card instanceof IHasManeuvers) {
                $maneuvers[] = $card;
            }
        }
        return $maneuvers;
    }

    public function getCardObjectFromDb($cardId) : Card 
    {
        $data = $this->getObjectFromDB("SELECT card_serialized FROM card WHERE card_id = $cardId");
        $card = unserialize($data['card_serialized']);
        return $card;
    }

    public function getCardIdByType($type) : ?int
    {
        $id = $this->getUniqueValueFromDB("SELECT card_id FROM card WHERE card_type = '$type'");
        return $id;
    }

    public function getDuelRows() : Array
    {
        $rounds = [];
        $duelId = $this->globals->get(Game::DUEL_ID);
        $sql = "
        SELECT 
            round as round,
            player_id as playerId,
            actor_id as actorId, 
            actor_serialized as actorSerialized,
            challenger_id as challengerId,
            starting_challenger_threat as startingChallengerThreat,
            defender_id as defenderId,
            starting_defender_threat as startingDefenderThreat,
            technique_id as techniqueId,
            technique_name as techniqueName,
            technique_riposte as techniqueRiposte,
            technique_parry as techniqueParry,
            technique_thrust as techniqueThrust,
            maneuver_id as maneuverId,
            maneuver_name as maneuverName,
            maneuver_riposte as maneuverRiposte,
            maneuver_parry as maneuverParry,
            maneuver_thrust as maneuverThrust,
            combat_card_id as combatCardId,
            combat_riposte as combatRiposte,
            combat_parry as combatParry,
            combat_thrust as combatThrust,
            gambled,
            ending_challenger_threat as endingChallengerThreat,
            ending_defender_threat as endingDefenderThreat,
            wounds_taken as wounds
            FROM duel_round
            WHERE duel_id = $duelId";
        $rounds_result = $this->getCollectionFromDb($sql);

        foreach ($rounds_result as $round)
        {
            $row = [];
            $row['round'] = $round['round'];
            $row['playerId'] = $round['playerId'];
            $row['challengerId'] = $round['challengerId'];
            $row['defenderId'] = $round['defenderId'];
            $row['actorId'] = $round['actorId'];

            $actor = unserialize($round['actorSerialized']);
            $row['actor'] = $actor->getPropertyArray($this);

            $challenger = $this->theah->getCardById($round['challengerId']);
            if ( ! $challenger)
                $challenger = $this->getCardObjectFromDb($round['challengerId']);

            $row['challengerName'] = $challenger->Name;
            $row['startingChallengerThreat'] = $round['startingChallengerThreat'];

            $defender = $this->theah->getCardById($round['defenderId']);
            if ( ! $defender)
                $defender = $this->getCardObjectFromDb($round['defenderId']);
            
            $row['defenderName'] = $defender->Name;
            $row['startingDefenderThreat'] = $round['startingDefenderThreat'];

            $row['techniqueName'] = $round['techniqueName'];
            $row['techniqueRiposte'] = $round['techniqueRiposte'];
            $row['techniqueParry'] = $round['techniqueParry'];
            $row['techniqueThrust'] = $round['techniqueThrust'];

            $row['maneuverName'] = $round['maneuverName'];
            $row['maneuverRiposte'] = $round['maneuverRiposte'];
            $row['maneuverParry'] = $round['maneuverParry'];
            $row['maneuverThrust'] = $round['maneuverThrust'];

            if ($round['combatCardId'] == null)
                $row['combatCard'] = null;
            else 
            {
                $combatCard = $this->getCardObjectFromDb($round['combatCardId']);
                $row['combatCard'] = $combatCard->getPropertyArray($this);
            }
            $row['combatRiposte'] = $round['combatRiposte'];
            $row['combatParry'] = $round['combatParry'];
            $row['combatThrust'] = $round['combatThrust'];

            $row['gambled'] = $round['gambled'];

            $row['endingChallengerThreat'] = $round['endingChallengerThreat'];
            $row['endingDefenderThreat'] = $round['endingDefenderThreat'];
            $row['wounds'] = $round['wounds'];

            $rounds[] = $row;
        }

        return $rounds;
    }

    function getDuelOpponentId($actorId)
    {
        $duelId = $this->globals->get(Game::DUEL_ID);
        $sql = "SELECT challenger_id, defender_id FROM duel WHERE duel_id = $duelId";
        $duel = $this->getObjectListFromDB($sql)[0];
        if ($duel['challenger_id'] == $actorId) {
            return $duel['defender_id'];
        }
        return $duel['challenger_id'];
    }

    public function getPlayerChosenScheme($playerId)
    {
        $sql = "SELECT selected_scheme_id FROM player WHERE player_id = $playerId";
        $selectedSchemeId = $this->getUniqueValueFromDB($sql);
        return $this->getCardObjectFromDb($selectedSchemeId);
    }

    function getPlayerReknown($player_id) {
        return $this->getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id'");
    }

    //Find the player with lowest count of characters in play.  Ties are ignored.
    function getPlayerControllingFewestCharacters()
    {
        $players = $this->loadPlayersBasicInfos();

        //Build the city so we can get the character count for each player.
        $this->theah->buildCity();

        $lowestCount = 999;
        $lowestPlayerId = null;
        foreach ($players as $playerId => $player) {
            $count = $this->theah->getCharacterCountByPlayerId($playerId);
            if ($count == $lowestCount) {
                $lowestPlayerId = null;
            }
            else if ($count < $lowestCount) {
                $lowestCount = $count;
                $lowestPlayerId = $playerId;
            }
        }

        return [$lowestPlayerId, $lowestCount];
    }

    function getReknownForLocation($location) {
        return $this->globals->get($this->getReknownLocationName(addslashes($location)));
    }

    function setReknownForLocation($location, $reknown) {
        $this->globals->set($this->getReknownLocationName(addslashes($location)), $reknown);
    }

    function getReknownLocationName($location) {
        return "Reknown_" . $location;
    }

    function getControllerForLocation($location) {
        return $this->globals->get($this->getControllerLocationName(addslashes($location))) ?? 0;
    }

    function setControllerForLocation($location, $playerId) {
        $this->globals->set($this->getControllerLocationName(addslashes($location)), $playerId);
    }

    function getControllerLocationName($location) {
        return "Control_" . $location;
    }

    function incrementPlayerReknown($player_id, $inc) {
        $count = $this->getPlayerReknown($player_id);
        if ($inc != 0) {
            $count += $inc;
            $this->setPlayerReknown($player_id, $count);
        }
        return $count;
    }

    public function instantiateCard($cardId) : Card 
    {
        //Pull the first two characters of the card id to get the set
        $set = substr($cardId, 0, 2);

        switch ($set) {
            case '01':
                $set = "_7s5s";
                break;
            default:
                $set = "_7s5s";
        }

        $className = "\Bga\Games\SeventhSeaCityOfFiveSails\cards\\$set\_$cardId";
        $card = new $className();

        return $card;
    }

    public function handContainsCard(int $playerId)
    {
        $hand = $this->cards->getCardsInLocation(Game::LOCATION_HAND, $playerId);
        foreach ($hand as $handCard) {
            $card = $this->getCardObjectFromDb($handCard['id']);
            if ($card instanceof Attachment) {
                return true;
            }
        }
        return false;
    }

    public function handWealthCount(int $playerId)
    {
        $hand = $this->cards->getCardsInLocation(Game::LOCATION_HAND, $playerId);
        $wealth = 0;
        foreach ($hand as $handCard) {
            $card = $this->getCardObjectFromDb($handCard['id']);
            //Does card have the wealth trait?  Count as 2 if it does.
            if (in_array('Wealth', $card->Traits))
                $wealth += 2;
            else
                $wealth += 1;
        }

        return $wealth;
    }

    public function handHasAttachments(int $playerId)
    {
        $hand = $this->cards->getCardsInLocation(Game::LOCATION_HAND, $playerId);
        foreach ($hand as $handCard) {
            $card = $this->getCardObjectFromDb($handCard['id']);
            if ($card instanceof Attachment) {
                return true;
            }
        }
        return false;
    }

    public function characterHasAttachmentOfType($character, $type)
    {
        if ($character instanceof Character)
            return false;
        
        foreach ($character->Attachments as $attachment) {
            $card = $this->getCardObjectFromDb($attachment);
            if (in_array($type, $card->Traits)) {
                return true;
            }
        }
        return false;
    }

    function setNewPlayerOrder($firstPlayerId)
    {
        $playerNumber = 1;
        $this->DbQuery("UPDATE player SET turn_order = $playerNumber WHERE player_id = $firstPlayerId");
        $nextPlayerId = $this->getPlayerAfter($firstPlayerId);
        while ($firstPlayerId != $nextPlayerId) {
            $playerNumber++;
            $this->DbQuery("UPDATE player SET turn_order = $playerNumber WHERE player_id = $nextPlayerId");
            $nextPlayerId = $this->getPlayerAfter($nextPlayerId);
        }
    }

    function setPlayerReknown($playerId, $reknown) {
        $this->DbQuery("UPDATE player SET player_score='$reknown' WHERE player_id=$playerId");
    }

    function isInReactionState(int $state) : bool
    {
        $array = [
            States::HIGH_DRAMA_PLAYER_TURN_REACTIONS,
        ];
        return in_array($state, $array);
    }

    function canPlayerPressureLocation(int $attemptingPlayerId, string $location, string $statType, int $pressureType) : array
    {
        //Get an array of players to keep track of their influence at the location 
        $playerInfluences = $this->getCollectionFromDB("SELECT player_id FROM player");
        foreach ($playerInfluences as $playerId => $player) {
            $player["influence"] = 0;
            $playerInfluences[$playerId] = $player;
        }

        $charactersAtLocation = $this->theah->getCharactersAtLocation($location);
        foreach ($charactersAtLocation as $character) 
        {
            if (!$character->ControllerId) continue;

            if ($pressureType == Game::REPUTATION_MERITEE_PRESSURE_TYPE && $character->hasTrait("Mercenary"))
            {
                continue;
            }

            $player = $playerInfluences[$character->ControllerId];

            switch ($statType) {
                case Game::STAT_COMBAT:
                    $player['influence'] += $character->getCombatPressureValue();
                    break;
                case Game::STAT_FINESSE:
                    $player['influence'] += $character->getFinessePressureValue();
                    break;
                case Game::STAT_INFLUENCE:
                    $player['influence'] += $character->getInfluencePressureValue();
                    break;
            }
            $playerInfluences[$character->ControllerId] = $player;
        }

        //Get the player with the most influence
        $maxInfluence = 0;
        $maxPlayerId = 0;
        $totals = "";
        foreach ($playerInfluences as $playerId => $player) 
        {
            $totals .= "{$this->getPlayerNameById($playerId)}:({$player['influence']}) ";
            if ($player['influence'] > $maxInfluence) {
                $maxInfluence = $player['influence'];
                $maxPlayerId = $playerId;
            }
        }

        //Check for ties
        $ties = array_filter($playerInfluences, fn($player) => $player['influence'] == $maxInfluence);

        if ($pressureType == Game::NORMAL_PRESSURE_TYPE)
        {
            if (count($ties) > 1 || $attemptingPlayerId != $maxPlayerId) 
                return [false, $totals];

            return [true, $totals];
        }
        else if ($pressureType == Game::REPUTATION_MERITEE_PRESSURE_TYPE)
        {
            if ($attemptingPlayerId == $maxPlayerId || array_key_exists($attemptingPlayerId, $ties))
                return [true, $totals];

            return [false, $totals];
        }
        else
        {
            throw new \Exception("Invalid pressure type: $pressureType");
        }
    }

    function canPlayerClaim(int $attemptingPlayerId, Character $performer): Array
    {
        //Get an array of players to keep track of their influence at the location 
        $playerInfluences = $this->getCollectionFromDB("SELECT player_id FROM player");
        foreach ($playerInfluences as $playerId => $player) {
            $player["influence"] = 0;
            $playerInfluences[$playerId] = $player;
        }

        $pressureTypes = $this->theah->getPressureTypesForClaim($performer);

        //Get the total influence of the characters at the location
        $charactersAtLocation = $this->theah->getCharactersAtLocation($performer->Location);

        $claimType = $this->globals->get(Game::CLAIM_TYPE);
        if ($claimType == Game::CLAUDE_CLAIM_TYPE)
            $this->notifyAllPlayers('message', clienttranslate('Claude de la Roche\'s Reaction is in effect. '), []);

        foreach ($pressureTypes as $pressureType) 
        {
            foreach ($charactersAtLocation as $character) 
            {
                if (!$character->ControllerId) continue;

                //If Claude reaction has been activated, and claim is at Claude's location,
                //then we only want to count the performer and en garde characters
                if ($claimType == Game::CLAUDE_CLAIM_TYPE)
                {
                    $claude = $this->theah->getCardById($this->globals->get(Game::CLAUD_ID));
                    //If this claim is at Claude's location, and the character is not the performer or engaged then ignore it
                    if ($claude->Location == $performer->Location && 
                        $character->Id != $performer->Id && 
                        $character->Engaged)
                        continue;                    
                }
    
                $player = $playerInfluences[$character->ControllerId];

                switch ($pressureType) 
                {
                    case Game::STAT_COMBAT:
                        $player['influence'] += $character->getCombatPressureValue();
                        break;
                    case Game::STAT_FINESSE:
                        $player['influence'] += $character->getFinessePressureValue();
                        break;
                    case Game::STAT_INFLUENCE:
                        $player['influence'] += $character->getInfluencePressureValue();
                        break;
                }
                $playerInfluences[$character->ControllerId] = $player;
            }
        }

        //Get the player with the most influence
        $maxInfluence = 0;
        $maxPlayerId = 0;
        $totals = "";
        foreach ($playerInfluences as $playerId => $player) 
        {
            $totals .= "{$this->getPlayerNameById($playerId)}:({$player['influence']}) ";
            if ($player['influence'] > $maxInfluence) {
                $maxInfluence = $player['influence'];
                $maxPlayerId = $playerId;
            }
        }

        //Check for ties
        $ties = array_filter($playerInfluences, fn($player) => $player['influence'] == $maxInfluence);

        if (count($ties) > 1 || $attemptingPlayerId != $maxPlayerId) 
            return [false, $totals];

        return [true, $totals];
    }

    function revealFirstCardTypeFromCityDeck(int $playerId, string $type, bool $discardInsteadOfSink = false): ?Card
    {
        $count = $this->cards->countCardInLocation(Game::LOCATION_CITY_DECK);
        $cards = $this->getCardsOnTopOfCityDeck($count);
        $revealed = [];
        $names = [];
        $found = false;
        $cardFound = null;
        foreach ($cards as $cardInfo)
        {
            $card = $this->getCardObjectFromDb($cardInfo['id']);
            if ($type == "Attachment")
            {
                if ($card instanceof Attachment)
                {
                    $revealed[] = $cardInfo['id'];
                    $found = true;
                    $cardFound = $card;
                    $names[] = $card->Name;
                    break;
                }
            }
            else
            {
                if ($card->hasTrait($type))
                {
                    $revealed[] = $cardInfo['id'];
                    $found = true;
                    $cardFound = $card;
                    $names[] = $card->Name;
                    break;
                }
            }

            $revealed[] = $cardInfo['id'];
            $names[] = $card->Name;
            unset($card);                
        }

        // Per rules team, if no mercenary is found, shuffle the discard pile into the deck and try again.
        if ( ! $found)
        {
            $this->shuffleCityDiscardIntoCityDeck();

            //Stick cards already revealed in the top of the deck
            $revealed = array_reverse($revealed);
            foreach ($revealed as $cardId)
            {
                $this->cards->insertCardOnExtremePosition($cardId, Game::LOCATION_CITY_DECK, true);
            }

            $revealed = [];
            $names = [];
            $count = $this->cards->countCardInLocation(Game::LOCATION_CITY_DECK);
            $cards = $this->getCardsOnTopOfCityDeck($count);    
            foreach ($cards as $cardInfo)
            {
                $card = $this->getCardObjectFromDb($cardInfo['id']);
                if ($type == "Attachment")
                {
                    if ($card instanceof Attachment)
                    {
                        $revealed[] = $cardInfo['id'];
                        $found = true;
                        $cardFound = $card;
                        $names[] = $card->Name;
                        break;
                    }
                }
                else
                {
                    if (in_array($type, $card->Traits))
                    {
                        $revealed[] = $cardInfo['id'];
                        $found = true;
                        $cardFound = $card;
                        $names[] = $card->Name;
                        break;
                    }
                }

                $revealed[] = $cardInfo['id'];
                $names[] = $card->Name;
                unset($card);
            }
        }

        $names = implode(", ", $names);
        $this->notifyAllPlayers("message", clienttranslate('A total of ${count} City Cards were revealed: ${names}'), [
            'count' => count($revealed),
            'names' => $names,
        ]);

        //Send the revealed cards (sans the found card) to the discard pile
        if ($discardInsteadOfSink)
        {
            foreach ($revealed as $cardId)
            {
                if ($cardId != $cardFound->Id)
                {
                    $event = EventFactory::createCardAddedToCityDiscardPileEvent($playerId, $cardId, Game::LOCATION_CITY_DECK);
                    $this->theah->queueEvent($event);
                }
            }
        }
        else
        {
            shuffle($revealed);
            foreach ($revealed as $cardId)
            {
                if ($cardId != $cardFound->Id)
                {
                    $this->cards->insertCardOnExtremePosition($cardId, Game::LOCATION_CITY_DECK, false);
                }
            }
        }

        $this->globals->set(Game::REVEALED_CARDS, json_encode($revealed));

        return $cardFound;
    }
}
