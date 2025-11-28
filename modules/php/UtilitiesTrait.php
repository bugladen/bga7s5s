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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IFactionCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskAttachment;

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
            technique_riposte as techniqueRiposte,
            technique_parry as techniqueParry,
            technique_thrust as techniqueThrust,
            maneuver_riposte as maneuverRiposte,
            maneuver_parry as maneuverParry,
            maneuver_thrust as maneuverThrust,
            combat_riposte as combatRiposte,
            combat_parry as combatParry,
            combat_thrust as combatThrust,
            gambled,
            ending_challenger_threat as endingChallengerThreat,
            ending_defender_threat as endingDefenderThreat,
            challenger_threat_is_lethal as challengerThreatIsLethal,
            defender_threat_is_lethal as defenderThreatIsLethal,
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

            //Some attachements have been destroyed from play so we need to buffer them
            //so they can be displayed
            $attachments = [];
            foreach ($actor->Attachments as $attachmentId)
            {
                $attachment = $this->theah->getCardById($attachmentId);
                if ($attachment)
                {
                    $attachments[] = $attachment->getPropertyArray($this);
                }
            }
            $row['attachments'] = $attachments;

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

            $sql = "SELECT technique_name FROM duel_round_technique where duel_id = $duelId AND round = {$round['round']}";
            $techniqueNames = $this->getCollectionFromDB($sql);

            if (count($techniqueNames) == 0)
                $row['techniqueName'] = null;
            else
            {
                $techniques = [];
                foreach ($techniqueNames as $techniqueName)
                {
                    $techniques[] = $techniqueName['technique_name'];
                }
                $row['techniqueNames'] = $techniques;
            }

            $row['techniqueRiposte'] = $round['techniqueRiposte'];
            $row['techniqueParry'] = $round['techniqueParry'];
            $row['techniqueThrust'] = $round['techniqueThrust'];

            $sql = "SELECT maneuver_name FROM duel_round_maneuver where duel_id = $duelId AND round = {$round['round']}";
            $maneuverNames = $this->getCollectionFromDB($sql);

            if (count($maneuverNames) == 0)
                $row['maneuverNames'] = null;
            else
            {
                $maneuvers = [];
                foreach ($maneuverNames as $maneuverName)
                {
                    $maneuvers[] = $maneuverName['maneuver_name'];
                }
                $row['maneuverNames'] = $maneuvers;
            }

            $row['maneuverRiposte'] = $round['maneuverRiposte'];
            $row['maneuverParry'] = $round['maneuverParry'];
            $row['maneuverThrust'] = $round['maneuverThrust'];

            $sql = "SELECT combat_card_id FROM duel_round_combat_card where duel_id = $duelId AND round = {$round['round']}";
            $combatCardIds = $this->getCollectionFromDB($sql);

            if (count($combatCardIds) == 0)
                $row['combatCards'] = null;
            else 
            {
                $combatCards = [];
                foreach ($combatCardIds as $combatCardId)
                {
                    $combatCard = $this->getCardObjectFromDb($combatCardId['combat_card_id']);
                    $combatCards[] = $combatCard->getPropertyArray($this);
                }
                $row['combatCards'] = $combatCards;
            }
            $row['combatRiposte'] = $round['combatRiposte'];
            $row['combatParry'] = $round['combatParry'];
            $row['combatThrust'] = $round['combatThrust'];

            $row['gambled'] = $round['gambled'];

            $row['endingChallengerThreat'] = $round['endingChallengerThreat'];
            $row['endingDefenderThreat'] = $round['endingDefenderThreat'];
            $row['challengerThreatIsLethal'] = $round['challengerThreatIsLethal'];
            $row['defenderThreatIsLethal'] = $round['defenderThreatIsLethal'];
            $row['wounds'] = $round['wounds'];

            $rounds[] = $row;
        }

        return $rounds;
    }

    public function getCombatCardRiposteForRound(int $duelId, int $round)
    {
        $sql = "SELECT combat_card_id FROM duel_round_combat_card where duel_id = $duelId AND round = {$round}";
        $combatCardIds = $this->getCollectionFromDB($sql);
        $riposte = 0;
        foreach ($combatCardIds as $combatCardId)
        {
            $card = $this->getCardObjectFromDb($combatCardId['combat_card_id']);
            if ($card instanceof IFactionCard)
            {
                $riposte += $card->getRiposte();
            }
        }

        return $riposte;
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

    public function getCardClassName(string $cardClass): string
    {
        //Pull the first two characters of the card id to get the set
        $set = substr($cardClass, 0, 2);

        switch ($set) {
            case '01':
                $set = "_7s5s";
                break;
            default:
                $set = "_7s5s";
        }

        return "\Bga\Games\SeventhSeaCityOfFiveSails\cards\\$set\_$cardClass";
    }

    public function instantiateCard(string $cardClass, ?int $id = null) : Card 
    {
        $className = $this->getCardClassName($cardClass);
        $card = new $className();
        if ($id) 
        {
            $card->setId($id);
        }

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
            if ($card->hasTrait("Wealth"))
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

    public function characterHasAttachmentOfType(Character $character, $type)
    {
        foreach ($character->Attachments as $attachment) {
            $attachment = $this->getCardObjectFromDb($attachment);
            if ($attachment instanceof Attachment && $attachment->hasTrait($type) && $attachment->hasEquipRestriction($type)) {
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

    function pressureLocation(int $attemptingPlayerId, Character $performer, string $pressureType): Array
    {
        //Get an array of players to keep track of their influence at the location 
        $playerInfluences = $this->getCollectionFromDB("SELECT player_id FROM player");
        foreach ($playerInfluences as $playerId => $player) {
            $player["influence"] = 0;
            $playerInfluences[$playerId] = $player;
        }

        $pressureStats = $this->theah->getPressureStats($performer, $pressureType);

        //Get the total influence of the characters at the location
        $charactersAtLocation = $this->theah->getCharactersAtLocation($performer->Location);

        //If Claude reaction has been activated, and claim is at Claude's location,
        //then we only want to count the performer and en garde characters
        if ($this->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::CLAUDE_PRESSURE_TYPE))
        {
            $claude = $this->theah->getCardById($this->globals->get(Game::CLAUD_ID));
            if ($claude->Location == $performer->Location)
            {
                $charactersAtLocation = array_filter($charactersAtLocation, fn($character) => $character->Id == $performer->Id || ! $character->Engaged);
            }
        }

        if ($this->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::REPUTATION_MERITEE_PRESSURE_TYPE))
        {
            $charactersAtLocation = array_filter($charactersAtLocation, fn($character) => ! $character->hasTrait("Mercenary"));
        }

        foreach ($pressureStats as $pressureStat) 
        {
            foreach ($charactersAtLocation as $character) 
            {
                if (!$character->isControlled()) continue;
                $playerId = $character->ControllerId;

                if ($this->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::PULL_THE_STRAND_PRESSURE_TYPE))
                {
                    if ($character->Id == $this->globals->get(Game::CHOSEN_TARGET))
                    {
                        $playerId = $performer->ControllerId;
                    }
                }

                switch ($pressureStat) 
                {
                    case Game::STAT_COMBAT:
                        $playerInfluences[$playerId]['influence'] += $character->getCombatPressureValue($this->theah, $performer->Location);
                        break;
                    case Game::STAT_FINESSE:
                        $playerInfluences[$playerId]['influence'] += $character->getFinessePressureValue($this->theah, $performer->Location);
                        break;
                    case Game::STAT_INFLUENCE:
                        $playerInfluences[$playerId]['influence'] += $character->getInfluencePressureValue($this->theah, $performer->Location);
                        break;
                    case Game::STAT_RESOLVE:
                        $playerInfluences[$playerId]['influence'] += $character->getResolvePressureValue($this->theah, $performer->Location);
                        break;
                }
            }

            //If Constanzo is in play, he gets 1 influence for each pressure type
            if ($this->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::CONSTANZO_PRESSURE_TYPE))
            {
                $constanzo = $this->theah->getCardById($this->globals->get(Game::CONSTANZO_ID));
                $playerInfluences[$constanzo->ControllerId]['influence'] += 1;
            }

            //If Pack Tactics is in play, add the Influence pressure bonus
            if ($this->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::PACK_TACTICS_PRESSURE_TYPE) && $pressureStat == Game::STAT_INFLUENCE)
            {
                $playerInfluences[$character->ControllerId]['influence'] += $this->globals->get(Game::PRESSURE_BONUS, 0);
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

        //Find the difference between the max influence and the next highest influence of the other players
        if (count($playerInfluences) == 1)
        {
            $difference = $maxInfluence;
        }
        else
        {
            $nextHighestInfluence = 0;
            foreach ($playerInfluences as $playerId => $player)
            {
                if ($playerId == $maxPlayerId) continue;
                if ($player['influence'] > $nextHighestInfluence)
                {
                    $nextHighestInfluence = $player['influence'];
                }
            }
            $difference = $maxInfluence - $nextHighestInfluence;
        }

        //Check for ties
        $ties = array_filter($playerInfluences, fn($player) => $player['influence'] == $maxInfluence);
        if (count($ties) > 1)
            $difference = 0;

        if ($this->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::TABARD_PRESSURE_TYPE)
            || $this->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::REPUTATION_MERITEE_PRESSURE_TYPE)
            || $this->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::CONTEMPT_AND_HATRED_PRESSURE_TYPE)
        )
        {
            //Ties win
            if ($attemptingPlayerId == $maxPlayerId || array_key_exists($attemptingPlayerId, $ties))
                return [true, $totals, $difference];

            return [false, $totals, $difference];
        }
        else
        {
            //Ties do not win
            if (count($ties) > 1 || $attemptingPlayerId != $maxPlayerId) 
                return [false, $totals, $difference];

            return [true, $totals, $difference];
        }
    }

    function revealFirstCardTypeFromCityDeck(int $playerId, string $type, int $sourceId = 0, bool $isEffect = false, bool $discardInsteadOfSink = false): ?Card
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
                    $names[] = $card->getInjectCode();
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
                    $names[] = $card->getInjectCode();
                    break;
                }
            }

            $revealed[] = $cardInfo['id'];
            $names[] = $card->getInjectCode();
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
                        $names[] = $card->getInjectCode();
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
                        $names[] = $card->getInjectCode();
                        break;
                    }
                }

                $revealed[] = $cardInfo['id'];
                $names[] = $card->getInjectCode();
                unset($card);
            }
        }

        $names = implode(", ", $names);
        $this->notifyAllPlayers("message", clienttranslate('A total of ${count} City Cards were revealed: ${names}'), [
            'count' => count($revealed),
            'names' => $names,
        ]);

        $this->globals->set(Game::REVEALED_CARDS, json_encode($revealed));

        //Take the found card out of the revealed cards
        $revealed = array_filter($revealed, fn($cardId) => $cardId != $cardFound->Id);

        if ($discardInsteadOfSink)
        {
            //Send the revealed cards to the discard pile
            foreach ($revealed as $cardId)
            {
                $event = EventFactory::createCardAddedToCityDiscardPileEvent($playerId, $cardId, Game::LOCATION_CITY_DECK, $sourceId, $isEffect);
                $this->theah->queueEvent($event);
            }
        }
        else
        {
            //Place the remaining cards at the bottom of the deck in a random order
            shuffle($revealed);
            foreach ($revealed as $cardId)
            {
                $this->cards->insertCardOnExtremePosition($cardId, Game::LOCATION_CITY_DECK, false);
            }
        }

        return $cardFound;
    }

    public function setGlobalFlag(string $variable, int $flag)
    {
        $global = $this->globals->get($variable);
        $this->globals->set($variable, $global | $flag);
    }

    public function isGlobalFlagSet(string $variable, int $flag)
    {
        $global = $this->globals->get($variable);
        
        //Return true if the flag is set
        return ($global & $flag) == $flag;
    }

    public function createRiskAttachment(Game $game, string $className, int $originalCardId, string $location, int $ownerId, int $controllerId, int $targetId)
    {
        //Place original card in special hiding location
        $owner = $game->theah->getCardById($originalCardId);
        $deck = $game->getGameDeckObject();
        $deck->moveCard($owner->Id, Game::LOCATION_PERMANENTLY_HIDDEN);

        $moveEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($owner->ControllerId, $owner->Id);
        $game->theah->queueEvent($moveEvent);

        $card = $game->createCardInLocation($className, $location, $ownerId, $controllerId);
        if ($card instanceof IRiskAttachment)
        {
            $card->setOriginalCardId($owner->Id);
        }
        $game->updateCardObjectInDb($card);

        $event = EventFactory::createAttachmentEquippedEvent($controllerId, $targetId, $card->Id, 0, 0, $asAction = false);
        $game->theah->queueEvent($event);
    }

    public function hasEquipRestrictions(Character $character, Attachment $attachment) : array
    {
        if ($attachment->hasTrait("Armor") && $this->characterHasAttachmentOfType($character, "Armor") && $attachment->hasEquipRestriction("Armor")) {
            return [true, $this->translate("Character cannot have more than one Armor attachment.")];
        }
        if ($attachment->hasTrait("Attire") && $this->characterHasAttachmentOfType($character, "Attire") && $attachment->hasEquipRestriction("Attire")) {
            return [true, $this->translate("Character cannot have more than one Attire attachment.")];
        }
        if ($attachment->hasTrait("Weapon") && $this->characterHasAttachmentOfType($character, "Weapon") && $attachment->hasEquipRestriction("Weapon")) {
            return [true, $this->translate("Character cannot have more than one Weapon attachment.")];
        }
        return [false, ""];
    }

    public function characterIsInDiscardOrLocker(Character $character) : bool
    {
        return strpos($character->Location, "Discard-") !== false || strpos($character->Location, "Locker-") !== false;
    }

}
