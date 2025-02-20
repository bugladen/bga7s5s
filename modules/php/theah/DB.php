<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

/** @disregard P1012 */
class DB extends \APP_DbObject
{
    public function queueEvent(Event $event)
    {
        $priority = $event->priority;
        $serialized = addslashes(serialize($event));
        $sql = "INSERT INTO events (event_priority, event_serialized) values ($priority, '{$serialized}')";
        /** @disregard P1012 */
        $this->DbQuery($sql);
    }

    public function getNextEvent()
    {
        $sql = "SELECT event_id as id, event_serialized as json FROM events ORDER BY event_priority LIMIT 1";
        /** @disregard P1012 */
        $data = $this->getObjectFromDB($sql);

        if (!$data) {
            return null;
        }
        
        $sql = "DELETE FROM events WHERE event_id = {$data['id']}";
        /** @disregard P1012 */
        $this->DbQuery($sql);

        $event = unserialize($data['json']);
        return $event;
    }

    public function getCollection(string $sql): array
    {
        /** @disregard P1012 */
        return $this->getCollectionFromDB($sql);
    }

    public function getObjectList(string $sql): array
    {
        /** @disregard P1012 */
        return $this->getObjectListFromDB($sql);
    }

    public function executeSql(string $sql)
    {
        /** @disregard P1012 */
        $this->DbQuery($sql);
    }

    public function getCardObjectsAtLocation(string $location, $playerId = null): array
    {
        $location = addslashes($location);
        $sql = "
            SELECT card_id as id, card_location_arg as playerId, card_serialized as json
            FROM card 
            WHERE card_location = '$location'
            ";
        if ($playerId) {
            $sql .= " AND card_location_arg = $playerId";
        }
        /** @disregard P1012 */
        $data = $this->getObjectListFromDB($sql);

        $cards = [];
        foreach ($data as $result) {
            $cards[(int)$result['id']] = unserialize($result['json']);
        }

        return $cards;
    }

    public function getCardObject($cardId) : Card {
        /** @disregard P1012 */
        $data = $this->getObjectFromDB("SELECT card_serialized FROM card WHERE card_id = $cardId");
        $card = unserialize($data['card_serialized']);
        return $card;
    }

    public function updateCardObject($card) {
        $serialized = addslashes(serialize($card));
        $sql = "UPDATE card set card_serialized = '{$serialized}' WHERE card_id = $card->Id";
        /** @disregard P1012 */
        $this->DbQuery($sql);
    }

    public function getPlayerReknown($playerId) {
        $sql = "SELECT player_score FROM player WHERE player_id = $playerId";
        /** @disregard P1012 */
        return $this->getUniqueValueFromDB($sql);
    }

    function setPlayerReknown($playerId, $reknown) {
        /** @disregard P1012 */
        $this->DbQuery("UPDATE player SET player_score='$reknown' WHERE player_id=$playerId");
    }

    function incrementPlayerReknown($player_id, $inc) {
        $count = $this->getPlayerReknown($player_id);
        if ($inc != 0) {
            $count += $inc;
            $this->setPlayerReknown($player_id, $count);
        }
        return $count;
    }

    function updateRoundWithCombatStats(int $duelId, int $round, string $mode, int $eventRiposte, int $eventParry, int $eventThrust): array
    {
        $sql = "SELECT r.actor_id, d.challenger_id, d.defender_id, r.ending_challenger_threat, r.ending_defender_threat
        FROM duel_round r JOIN duel d ON r.duel_id = d.duel_id WHERE r.duel_id = $duelId AND r.round = $round";

        $result = $this->getObjectList($sql)[0];
        $actorId = $result['actor_id'];
        $challengerId = $result['challenger_id'];
        $defenderId = $result['defender_id'];
        $endingChallengerThreat = $result['ending_challenger_threat'];
        $endingDefenderThreat = $result['ending_defender_threat'];
        $wounds = 0;

        $results = [];
        $results['endingChallengerThreatBefore'] = $endingChallengerThreat;
        $results['endingDefenderThreatBefore'] = $endingDefenderThreat;

        if ($actorId == $challengerId)
        {
            //Riposte sends threat back to adversary, only in the amount it reduced threat to the actor
            $riposte = $eventRiposte;
            if ($riposte > $endingChallengerThreat) $riposte = $endingChallengerThreat;
            $endingChallengerThreat -= $riposte;
            $endingDefenderThreat += $riposte;
            $results['riposte'] = $riposte;

            //Parry reduces threat
            $parry = $eventParry;
            if ($parry > $endingChallengerThreat) $parry = $endingChallengerThreat;
            $endingChallengerThreat -= $parry;
            $results['parry'] = $parry;

            //Thrust adds threat
            $endingDefenderThreat += $eventThrust;
            $results['thrust'] = $eventThrust;

            $wounds = $endingChallengerThreat;
        }
        else if ($actorId == $defenderId)
        {
            //Riposte sends threat back to adversary, only in the amount it reduced threat to the actor
            $riposte = $eventRiposte;
            if ($riposte > $endingDefenderThreat) $riposte = $endingDefenderThreat;
            $endingDefenderThreat -= $riposte;
            $endingChallengerThreat += $riposte;
            $results['riposte'] = $riposte;

            //Parry reduces threat
            $parry = $eventParry;
            if ($parry > $endingDefenderThreat) $parry = $endingDefenderThreat;
            $endingDefenderThreat -= $parry;
            $results['parry'] = $parry;

            //Thrust adds threat
            $endingChallengerThreat += $eventThrust;
            $results['thrust'] = $eventThrust;

            $wounds = $endingDefenderThreat;
        }

        $results['endingChallengerThreatAfter'] = $endingChallengerThreat;
        $results['endingDefenderThreatAfter'] = $endingDefenderThreat;
        $results['wounds'] = $wounds;

        $sql = "UPDATE duel_round SET 
            {$mode}_riposte = {$eventRiposte}, 
            {$mode}_parry = {$eventParry}, 
            {$mode}_thrust = {$eventThrust},
            ending_challenger_threat = $endingChallengerThreat,
            ending_defender_threat = $endingDefenderThreat,
            wounds_taken = $wounds 
            WHERE duel_id = $duelId AND round = $round";

        $this->executeSql($sql);

        return $results;
    }
}