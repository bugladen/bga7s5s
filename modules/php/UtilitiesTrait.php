<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use ArrayAccess;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

trait UtilitiesTrait
{
    function dbSetAuxScore($player_id, $score) {
        $this->DbQuery("UPDATE player SET player_score_aux=$score WHERE player_id='$player_id'");
    }

    public function getAttachmentsInHand(int $playerId)
    {
        $hand = $this->cards->getCardsInLocation('hand', $playerId);
        $attachments = [];
        foreach ($hand as $handCard) {
            $card = $this->getCardObjectFromDb($handCard['id']);
            if ($card instanceof Attachment) {
                $attachments[] = $card;
            }
        }
        return $attachments;
    }

    public function getCardObjectFromDb($cardId) : Card {
        $data = $this->getObjectFromDB("SELECT card_serialized FROM card WHERE card_id = $cardId");
        $card = unserialize($data['card_serialized']);
        return $card;
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
            d.challenger_id as challengerId,
            challenger_threat as startingChallengerThreat,
            d.defender_id as defenderId,
            defender_threat as startingDefenderThreat,
            technique_id as techniqueId,
            technique_riposte as techniqueRiposte,
            technique_parry as techniqueParry,
            technique_thrust as techniqueThrust,
            maneuver_id as maneuverId,
            maneuver_riposte as maneuverRiposte,
            maneuver_parry as maneuverParry,
            maneuver_thrust as maneuverThrust,
            combat_card_id as combatCardId,
            combat_riposte as combatRiposte,
            combat_parry as combatParry,
            combat_thrust as combatThrust,
            ending_challenger_threat as endingChallengerThreat,
            ending_defender_threat as endingDefenderThreat
            FROM duel_round r
            INNER JOIN duel d ON d.duel_id = r.duel_id
            WHERE r.duel_id = $duelId";
        $rounds_result = $this->getCollectionFromDb($sql);

        foreach ($rounds_result as $round)
        {
            $row = [];
            $row['round'] = $round['round'];
            $row['playerId'] = $round['playerId'];
            $row['challengerId'] = $round['challengerId'];
            $row['defenderId'] = $round['defenderId'];
            $row['actorId'] = $round['actorId'];

            $challenger = $this->getCardObjectFromDb($round['challengerId']);
            $row['challengerName'] = $challenger->Name;
            $row['startingChallengerThreat'] = $round['startingChallengerThreat'];

            $defender = $this->getCardObjectFromDb($round['defenderId']);
            $row['defenderName'] = $defender->Name;
            $row['startingDefenderThreat'] = $round['startingDefenderThreat'];

            $row['techniqueName'] = null;
            if ($round['techniqueId'] != null) {
                $technique = $this->theah->getTechniqueById($round['techniqueId']);
                $row['techniqueName'] = $technique->Name;
            }
            $row['techniqueRiposte'] = $round['techniqueRiposte'];
            $row['techniqueParry'] = $round['techniqueParry'];
            $row['techniqueThrust'] = $round['techniqueThrust'];

            $row['maneuverName'] = null;
            if ($round['maneuverId'] != null) {
                $maneuver = $this->theah->getManeuverById($round['maneuverId']);
                $row['maneuverName'] = $maneuver->Name;
            }
            $row['maneuverRiposte'] = $round['maneuverRiposte'];
            $row['maneuverParry'] = $round['maneuverParry'];
            $row['maneuverThrust'] = $round['maneuverThrust'];

            $row['combatCardId'] = $round['combatCardId'];
            $row['combatRiposte'] = $round['combatRiposte'];
            $row['combatParry'] = $round['combatParry'];
            $row['combatThrust'] = $round['combatThrust'];

            $row['endingChallengerThreat'] = $round['endingChallengerThreat'];
            $row['endingDefenderThreat'] = $round['endingDefenderThreat'];

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

    function getGameDeckObject() {
        return $this->cards;
    }

    function getPlayerFactionDeckName($playerId) {
        return "Faction-$playerId";
    }

    function getPlayerDiscardDeckName($playerId) {
        return "Discard-$playerId";
    }

    function getPlayerLockerName($playerId) {
        return "Locker-$playerId";
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

    protected function instantiateCard($cardId) : Card {

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

    public function handHasAttachments(int $playerId)
    {
        $hand = $this->cards->getCardsInLocation('hand', $playerId);
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

    public function playerDrawCard($playerId): Card
    {
        $location = $this->getPlayerFactionDeckName($playerId);
        $cardInfo = $this->cards->pickCard($location, $playerId);
        $card = $this->getCardObjectFromDb($cardInfo['id']);
        $card->ControllerId = $playerId;
        $card->OwnerId = $playerId;
        $card->Location = Game::LOCATION_HAND;
        $this->updateCardObjectInDb($card);

        return $card;
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

}