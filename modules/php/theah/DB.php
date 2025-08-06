<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

/** @disregard */
class DB extends \APP_DbObject
{
    public function queueEvent(Event $event)
    {
        $priority = $event->priority;
        $serialized = addslashes(serialize($event));
        $sql = "INSERT INTO events (event_priority, event_serialized) values ($priority, '{$serialized}')";
        /** @disregard P1013 */
        $this->DbQuery($sql);
    }

    public function getNextEvent()
    {
        $sql = "SELECT event_id as id, event_serialized as json FROM events ORDER BY event_priority LIMIT 1";
        $data = $this->getObject($sql);

        if (!$data) {
            return null;
        }
        
        $sql = "DELETE FROM events WHERE event_id = {$data['id']}";
        /** @disregard P1013 */
        $this->DbQuery($sql);

        $event = unserialize($data['json']);
        return $event;
    }

    public function deleteManeuverEvents(string $maneuverId)
    {
        $sql = "DELETE FROM events 
                WHERE (event_serialized LIKE '%EventResolveManeuver%' AND event_serialized LIKE '%{$maneuverId}%')
                OR (event_serialized LIKE '%EventDuelCalculateManeuverValues%' AND event_serialized LIKE '%{$maneuverId}%')";
        $this->executeSql($sql);
    }

    public function deleteTechniqueEvents(string $techniqueId)
    {
        $sql = "DELETE FROM events 
                WHERE (event_serialized LIKE '%EventResolveTechnique%' AND event_serialized LIKE '%{$techniqueId}%')
                OR (event_serialized LIKE '%EventDuelCalculateTechniqueValues%' AND event_serialized LIKE '%{$techniqueId}%')";
        $this->executeSql($sql);
    }

    public function getCollection(string $sql): array
    {
        /** @disregard P1013 */
        return $this->getCollectionFromDB($sql);
    }

    public function getObject(string $sql): array | null
    {
        /** @disregard P1013 */
        return $this->getObjectFromDB($sql);
    }

    public function getObjectList(string $sql): array
    {
        /** @disregard P1013 */
        return $this->getObjectListFromDB($sql);
    }

    public function getUniqueValue(string $sql)
    {
        /** @disregard P1013 */
        return $this->getUniqueValueFromDB($sql);
    }

    public function executeSql(string $sql)
    {
        /** @disregard P1013 */
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
        /** @disregard P1013 */
        $data = $this->getObjectListFromDB($sql);

        $cards = [];
        foreach ($data as $result) {
            $cards[(int)$result['id']] = unserialize($result['json']);
        }

        return $cards;
    }

    public function getCardObject($cardId) : Card {
        /** @disregard P1013 */
        $data = $this->getObjectFromDB("SELECT card_serialized FROM card WHERE card_id = $cardId");
        $card = unserialize($data['card_serialized']);
        return $card;
    }

    public function updateCardObject($card) {
        $serialized = addslashes(serialize($card));
        $sql = "UPDATE card set card_serialized = '{$serialized}' WHERE card_id = $card->Id";
        /** @disregard P1013 */
        $this->DbQuery($sql);
    }

    public function getPlayerIds() {
        $sql = "SELECT player_id as id FROM player";
        /** @disregard P1013 */
        return $this->getObjectListFromDB($sql);
    }

    public function getPlayerReknown($playerId) {
        $sql = "SELECT player_score FROM player WHERE player_id = $playerId";
        /** @disregard P1013 */
        return $this->getUniqueValueFromDB($sql);
    }

    function setPlayerReknown($playerId, $reknown) {
        /** @disregard P1013 */
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
            $riposte = $eventRiposte < 0 ? 0 : $eventRiposte;
            if ($riposte > $endingChallengerThreat) 
                $riposte = $endingChallengerThreat;
            $endingChallengerThreat -= $riposte;
            $endingDefenderThreat += $riposte;
            $results['riposte'] = $eventRiposte;

            //Parry reduces threat
            $parry = $eventParry < 0 ? 0 : $eventParry;
            if ($parry > $endingChallengerThreat) 
                $parry = $endingChallengerThreat;
            $endingChallengerThreat -= $parry;
            $results['parry'] = $eventParry;

            //Thrust adds threat
            $thrust = $eventThrust < 0 ? 0 : $eventThrust;
            $endingDefenderThreat += $thrust;
            $results['thrust'] = $eventThrust;

            $wounds = $endingChallengerThreat;
        }
        else if ($actorId == $defenderId)
        {
            //Riposte sends threat back to adversary, only in the amount it reduced threat to the actor
            $riposte = $eventRiposte < 0 ? 0 : $eventRiposte;
            if ($riposte > $endingDefenderThreat) 
                $riposte = $endingDefenderThreat;
            $endingDefenderThreat -= $riposte;
            $endingChallengerThreat += $riposte;
            $results['riposte'] = $eventRiposte;
         
            //Parry reduces threat
            $parry = $eventParry < 0 ? 0 : $eventParry;
            if ($parry > $endingDefenderThreat) 
                $parry = $endingDefenderThreat;
            $endingDefenderThreat -= $parry;
            $results['parry'] = $eventParry;

            //Thrust adds threat
            $thrust = $eventThrust < 0 ? 0 : $eventThrust;
            $endingChallengerThreat += $thrust;
            $results['thrust'] = $eventThrust;

            $wounds = $endingDefenderThreat;
        }

        $results['endingChallengerThreatAfter'] = $endingChallengerThreat;
        $results['endingDefenderThreatAfter'] = $endingDefenderThreat;
        $results['wounds'] = $wounds;

        $sql = "UPDATE duel_round SET 
            {$mode}_riposte = {$mode}_riposte + {$eventRiposte}, 
            {$mode}_parry = {$mode}_parry + {$eventParry}, 
            {$mode}_thrust = {$mode}_thrust + {$eventThrust},
            ending_challenger_threat = $endingChallengerThreat,
            ending_defender_threat = $endingDefenderThreat,
            wounds_taken = $wounds 
            WHERE duel_id = $duelId AND round = $round";

        $this->executeSql($sql);

        return $results;
    }

    public function updateRoundThreats(int $duelId, int $round, int $challengerThreat, int $defenderThreat, ?bool $challengerThreatIsLethal = null, ?bool $defenderThreatIsLethal = null): void
    {
        $sql = "
        SELECT d.challenger_id, d.defender_id, r.actor_id, r.wounds_taken 
        FROM duel d
        JOIN duel_round r ON d.duel_id = r.duel_id 
        WHERE d.duel_id = $duelId AND r.round = $round";

        $result = $this->getObject($sql);
        $challengerId = $result['challenger_id'];
        $defenderId = $result['defender_id'];
        $actorId = $result['actor_id'];
        $wounds = $result['wounds_taken'];

        $adjustedWounds = $wounds;
        if ($actorId == $challengerId)
        {
            $adjustedWounds += $challengerThreat;
        }
        else if ($actorId == $defenderId)
        {
            $adjustedWounds += $defenderThreat;
        }

        $sql = "UPDATE duel_round set 
            ending_challenger_threat = ending_challenger_threat + {$challengerThreat}, 
            ending_defender_threat = ending_defender_threat + {$defenderThreat},
            wounds_taken = $adjustedWounds
            WHERE duel_id = $duelId AND round = $round";

        $this->executeSql($sql);

        if ($challengerThreatIsLethal)
        {
            $lethal = $challengerThreatIsLethal ? 1 : 0;
            $sql = "UPDATE duel_round set challenger_threat_is_lethal = $lethal WHERE duel_id = $duelId AND round = $round";
            $this->executeSql($sql);
        }

        if ($defenderThreatIsLethal)
        {
            $lethal = $defenderThreatIsLethal ? 1 : 0;
            $sql = "UPDATE duel_round set defender_threat_is_lethal = $lethal WHERE duel_id = $duelId AND round = $round";
            $this->executeSql($sql);
        }
    }

    public function getRoundThreats(int $duelId, int $round): array
    {
        $sql = "SELECT ending_challenger_threat, ending_defender_threat, wounds_taken, challenger_threat_is_lethal, defender_threat_is_lethal FROM duel_round WHERE duel_id = $duelId AND round = $round";
        return $this->getObject($sql);
    }
}