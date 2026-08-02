<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

/** @disregard */
class DB
{
    private Game $game;

    public function __construct(Game $game)
    {
        $this->game = $game;
    }

    public function queueEvent(Event $event)
    {
        $priority = $event->priority;
        $serialized = addslashes(serialize($event));
        $sql = "INSERT INTO events (event_priority, event_serialized) values ($priority, '{$serialized}')";
        /** @disregard P1013 */
        $this->game->DbQuery($sql);
    }

    public function stackEvent(Event $event)
    {
        $priority = Event::HIGHEST_PRIORITY;
        $sql = "SELECT MIN(event_priority) FROM events";
        $result = $this->getUniqueValue($sql);
        if ($result !== NULL)
            $priority = intval($result) - 1;

        $event->priority = $priority;
        
        $event->wasStacked = true;
        $serialized = addslashes(serialize($event));
        $sql = "INSERT INTO events (event_priority, event_serialized) values ($priority, '{$serialized}')";
        $this->game->DbQuery($sql);
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
        $this->game->DbQuery($sql);

        $event = $this->game->safeUnserialize($data['json']);
        return $event;
    }

    public function deleteEventBatch(int $batchId)
    {
        $sql = "DELETE FROM events
                WHERE event_serialized LIKE '%batchId\";i:{$batchId}%'";
        $this->executeSql($sql);
    }

    public function deleteRenownAddedToLocationEventsByBatchId(int $batchId)
    {
        $sql = "DELETE FROM events
                WHERE event_serialized LIKE '%EventRenownAddedToLocation%'
                  AND event_serialized LIKE '%batchId\";i:{$batchId};%'";
        $this->executeSql($sql);
    }

    public function deleteRenownRemovedFromLocationEventsByBatchId(int $batchId)
    {
        $sql = "DELETE FROM events
                WHERE event_serialized LIKE '%EventRenownRemovedFromLocation%'
                  AND event_serialized LIKE '%batchId\";i:{$batchId};%'";
        $this->executeSql($sql);
    }

    public function deleteManeuverEvents(string $maneuverId)
    {
        $sql = "DELETE FROM events 
                WHERE event_serialized LIKE '%{$maneuverId}%'";
        $this->executeSql($sql);
    }

    public function deleteTechniqueEvents(string $techniqueId)
    {
        $sql = "DELETE FROM events 
                WHERE event_serialized LIKE '%{$techniqueId}%'";
        $this->executeSql($sql);
    }

    public function deletePressureResultEvents()
    {
        $sql = "DELETE FROM events 
                WHERE (event_serialized LIKE '%EventLocationPressureResult%')";
        $this->executeSql($sql);
    }

    public function areTransitionEventsOfTypeForPlayerQueued(int $playerId, string $reactionType): bool
    {
        $sql = "SELECT COUNT(*) FROM events 
                WHERE (event_serialized LIKE '%EventTransition%' AND event_serialized LIKE '%{$playerId}%' AND event_serialized LIKE '%{$reactionType}%')";
        return $this->getUniqueValue($sql) > 0;
    }

    //Use this to delete all reaction transition events that might pile up from other events
    public function deleteTransitionEvents(string $reactionId)
    {
        $sql = "DELETE FROM events 
                WHERE (event_serialized LIKE '%EventTransition%' AND event_serialized LIKE '%{$reactionId}%')";
        $this->executeSql($sql);
    }

    public function deleteTransitionEventsBySourceId(int $sourceId)
    {
        $sql = "DELETE FROM events 
                WHERE (event_serialized LIKE '%EventTransition%' AND event_serialized LIKE '%\"sourceId\";i:{$sourceId}%')";
        $this->executeSql($sql);
    }

    public function deleteEventsTargetingCard(int $cardId)
    {
        $sql = "DELETE FROM events 
                WHERE (event_serialized LIKE '%Id\";i:{$cardId}%')";
        $this->executeSql($sql);
    }

    public function deleteActionTriggeredEvents(string $actionId)
    {
        $sql = "DELETE FROM events 
                WHERE (event_serialized LIKE '%EventActionTriggered%' AND event_serialized LIKE '%{$actionId}%')";
        $this->executeSql($sql);
    }

    public function deleteRiskReactionTriggeredEvents(string $reactionId)
    {
        $sql = "DELETE FROM events
                WHERE (event_serialized LIKE '%EventRiskReactionTriggered%' AND event_serialized LIKE '%{$reactionId}%')";
        $this->executeSql($sql);
    }

    // WHY: Tight sourceId match (trailing ;) so card 1 does not match card 12.
    // Used by Night of Drinking to detect Risk Reaction plays on EventRiskPlayed
    // without also re-offering cancel on Risk Action plays (which already fired
    // EventActionActivated).
    public function areRiskReactionTriggeredEventsQueuedForSource(int $sourceId): bool
    {
        if ($sourceId <= 0)
        {
            return false;
        }
        $sql = "SELECT COUNT(*) FROM events
                WHERE (event_serialized LIKE '%EventRiskReactionTriggered%' AND event_serialized LIKE '%sourceId\";i:{$sourceId};%')";
        return $this->getUniqueValue($sql) > 0;
    }

    public function deleteRiskPlayedEvents(int $riskId)
    {
        if ($riskId <= 0)
        {
            return;
        }
        $sql = "DELETE FROM events
                WHERE (event_serialized LIKE '%EventRiskPlayed%' AND event_serialized LIKE '%riskId\";i:{$riskId};%')";
        $this->executeSql($sql);
    }

    public function getCollection(string $sql): array
    {
        /** @disregard P1013 */
        return $this->game->getCollectionFromDB($sql);
    }

    public function getObject(string $sql): array | null
    {
        /** @disregard P1013 */
        return $this->game->getObjectFromDB($sql);
    }

    public function getObjectList(string $sql): array
    {
        /** @disregard P1013 */
        return $this->game->getObjectListFromDB($sql);
    }

    public function getUniqueValue(string $sql)
    {
        /** @disregard P1013 */
        return $this->game->getUniqueValueFromDB($sql);
    }

    public function executeSql(string $sql)
    {
        /** @disregard P1013 */
        $this->game->DbQuery($sql);
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
        $data = $this->game->getObjectListFromDB($sql);

        $cards = [];
        foreach ($data as $result) {
            $cards[(int)$result['id']] = $this->game->safeUnserialize($result['json']);
        }

        return $cards;
    }

    public function getCardObject($cardId) : ?Card {
        if ($cardId === null || $cardId === '') {
            return null;
        }

        /** @disregard P1013 */
        $data = $this->game->getObjectFromDB("SELECT card_serialized FROM card WHERE card_id = $cardId");
        if ($data === null) {
            return null;
        }

        $card = $this->game->safeUnserialize($data['card_serialized']);
        return $card;
    }

    public function updateCardObject($card) {
        $serialized = addslashes(serialize($card));
        $sql = "UPDATE card set card_serialized = '{$serialized}' WHERE card_id = $card->Id";
        /** @disregard P1013 */
        $this->game->DbQuery($sql);
    }

    public function getPlayerIds() {
        $sql = "SELECT player_id as id FROM player";
        /** @disregard P1013 */
        return $this->game->getObjectListFromDB($sql);
    }

    public function getPlayerReknown($playerId) {
        $sql = "SELECT player_score FROM player WHERE player_id = $playerId";
        /** @disregard P1013 */
        return $this->game->getUniqueValueFromDB($sql);
    }

    function setPlayerReknown($playerId, $reknown) {
        /** @disregard P1013 */
        $this->game->bga->playerScore->set($playerId, $reknown);
        // $this->game->DbQuery("UPDATE player SET player_score='$reknown' WHERE player_id=$playerId");
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
        $sql = "SELECT r.actor_id, d.challenger_id, d.defender_id, 
        r.starting_challenger_threat, r.starting_defender_threat,
        r.ending_challenger_threat, r.ending_defender_threat, 
        r.challenger_threat_is_lethal, r.defender_threat_is_lethal,
        COALESCE(r.combat_riposte, 0) as combat_riposte, COALESCE(r.combat_parry, 0) as combat_parry, COALESCE(r.combat_thrust, 0) as combat_thrust,
        COALESCE(r.maneuver_riposte, 0) as maneuver_riposte, COALESCE(r.maneuver_parry, 0) as maneuver_parry, COALESCE(r.maneuver_thrust, 0) as maneuver_thrust,
        COALESCE(r.technique_riposte, 0) as technique_riposte, COALESCE(r.technique_parry, 0) as technique_parry, COALESCE(r.technique_thrust, 0) as technique_thrust
        FROM duel_round r JOIN duel d ON r.duel_id = d.duel_id WHERE r.duel_id = $duelId AND r.round = $round";

        $result = $this->getObjectList($sql)[0];
        $actorId = $result['actor_id'];
        $challengerId = $result['challenger_id'];
        $defenderId = $result['defender_id'];
        $challengerThreatIsLethal = $result['challenger_threat_is_lethal'];
        $defenderThreatIsLethal = $result['defender_threat_is_lethal'];
        $wounds = 0;

        // WHY live character (not getDuelRoundOpponent last-known): Riposte/Thrust put
        // threat on the adversary. If they are in discard/locker they cannot receive it —
        // discard instead. Last-known still has city Location/Engaged from before death.
        $adversaryId = ($actorId == $challengerId) ? $defenderId : $challengerId;
        $adversary = $this->game->theah->getCharacterById($adversaryId);
        $adversaryAbsent = $adversary === null || $this->game->characterIsInDiscardOrLocker($adversary);

        // For maneuvers, start from scratch with starting threats and sum all R/P/T before applying
        if ($mode == 'maneuver')
        {
            $endingChallengerThreat = $result['starting_challenger_threat'];
            $endingDefenderThreat = $result['starting_defender_threat'];
            
            // WHY: Include stored maneuver_* + technique_* so a second maneuver calc
            // (e.g. Technique_02043a clone, Katain) stacks instead of overwriting ending
            // threats as combat + this event only. Event values are the new contribution
            // and are added into maneuver_* by the UPDATE below.
            $totalRiposte = (int)$result['combat_riposte'] + (int)$result['maneuver_riposte'] + (int)$result['technique_riposte'] + $eventRiposte;
            $totalParry = (int)$result['combat_parry'] + (int)$result['maneuver_parry'] + (int)$result['technique_parry'] + $eventParry;
            $totalThrust = (int)$result['combat_thrust'] + (int)$result['maneuver_thrust'] + (int)$result['technique_thrust'] + $eventThrust;

            if ($actorId == $challengerId)
            {
                // Apply total Riposte
                $riposte = $totalRiposte;
                if ($riposte > $endingChallengerThreat)
                    $riposte = $endingChallengerThreat;
                if ($riposte < 0)
                    $riposte = 0;
                $endingChallengerThreat -= $riposte;
                if (! $adversaryAbsent)
                    $endingDefenderThreat += $riposte;

                // Apply total Parry
                $parry = $totalParry;
                if ($parry > $endingChallengerThreat)
                    $parry = $endingChallengerThreat;
                if ($parry < 0)
                    $parry = 0;
                $endingChallengerThreat -= $parry;

                // Apply total Thrust — clamp so opponent threat cannot go below 0
                // WHY: negative thrust is intentional (e.g. Technique_01050), but must not drive threat negative
                // Absent adversary: discard thrust (same as Riposte bounce).
                if (! $adversaryAbsent)
                {
                    $thrust = $totalThrust;
                    if ($endingDefenderThreat + $thrust < 0)
                        $thrust = -$endingDefenderThreat;
                    $endingDefenderThreat += $thrust;
                }
            }
            else if ($actorId == $defenderId)
            {
                // Apply total Riposte
                $riposte = $totalRiposte;
                if ($riposte > $endingDefenderThreat)
                    $riposte = $endingDefenderThreat;
                if ($riposte < 0)
                    $riposte = 0;
                $endingDefenderThreat -= $riposte;
                if (! $adversaryAbsent)
                    $endingChallengerThreat += $riposte;

                // Apply total Parry
                $parry = $totalParry;
                if ($parry > $endingDefenderThreat)
                    $parry = $endingDefenderThreat;
                if ($parry < 0)
                    $parry = 0;
                $endingDefenderThreat -= $parry;

                // Apply total Thrust — clamp so opponent threat cannot go below 0
                if (! $adversaryAbsent)
                {
                    $thrust = $totalThrust;
                    if ($endingChallengerThreat + $thrust < 0)
                        $thrust = -$endingChallengerThreat;
                    $endingChallengerThreat += $thrust;
                }
            }

            // Store the computed values in results (maneuver's contribution is already included in totals)
            $results = [];
            $results['endingChallengerThreatBefore'] = $result['starting_challenger_threat'];
            $results['endingDefenderThreatBefore'] = $result['starting_defender_threat'];
            $results['challengerThreatIsLethal'] = $challengerThreatIsLethal;
            $results['defenderThreatIsLethal'] = $defenderThreatIsLethal;
            $results['riposte'] = $eventRiposte;
            $results['parry'] = $eventParry;
            $results['thrust'] = $eventThrust;
            $results['endingChallengerThreatAfter'] = $endingChallengerThreat;
            $results['endingDefenderThreatAfter'] = $endingDefenderThreat;
            
            $wounds = ($actorId == $challengerId) ? $endingChallengerThreat : $endingDefenderThreat;
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
        // For techniques, start from scratch with starting threats and sum all R/P/T before applying
        else if ($mode == 'technique')
        {
            $endingChallengerThreat = $result['starting_challenger_threat'];
            $endingDefenderThreat = $result['starting_defender_threat'];
            
            // WHY: Include stored technique_* so a second technique calc in the same
            // round stacks. Event values are the new contribution and are added into
            // technique_* by the UPDATE below.
            $totalRiposte = (int)$result['combat_riposte'] + (int)$result['maneuver_riposte'] + (int)$result['technique_riposte'] + $eventRiposte;
            $totalParry = (int)$result['combat_parry'] + (int)$result['maneuver_parry'] + (int)$result['technique_parry'] + $eventParry;
            $totalThrust = (int)$result['combat_thrust'] + (int)$result['maneuver_thrust'] + (int)$result['technique_thrust'] + $eventThrust;

            if ($actorId == $challengerId)
            {
                // Apply total Riposte
                $riposte = $totalRiposte;
                if ($riposte > $endingChallengerThreat)
                    $riposte = $endingChallengerThreat;
                if ($riposte < 0)
                    $riposte = 0;
                $endingChallengerThreat -= $riposte;
                if (! $adversaryAbsent)
                    $endingDefenderThreat += $riposte;

                // Apply total Parry
                $parry = $totalParry;
                if ($parry > $endingChallengerThreat)
                    $parry = $endingChallengerThreat;
                if ($parry < 0)
                    $parry = 0;
                $endingChallengerThreat -= $parry;

                // Apply total Thrust — clamp so opponent threat cannot go below 0
                // WHY: negative thrust is intentional (e.g. Technique_01050), but must not drive threat negative
                // Absent adversary: discard thrust (same as Riposte bounce).
                if (! $adversaryAbsent)
                {
                    $thrust = $totalThrust;
                    if ($endingDefenderThreat + $thrust < 0)
                        $thrust = -$endingDefenderThreat;
                    $endingDefenderThreat += $thrust;
                }
            }
            else if ($actorId == $defenderId)
            {
                // Apply total Riposte
                $riposte = $totalRiposte;
                if ($riposte > $endingDefenderThreat)
                    $riposte = $endingDefenderThreat;
                if ($riposte < 0)
                    $riposte = 0;
                $endingDefenderThreat -= $riposte;
                if (! $adversaryAbsent)
                    $endingChallengerThreat += $riposte;

                // Apply total Parry
                $parry = $totalParry;
                if ($parry > $endingDefenderThreat)
                    $parry = $endingDefenderThreat;
                if ($parry < 0)
                    $parry = 0;
                $endingDefenderThreat -= $parry;

                // Apply total Thrust — clamp so opponent threat cannot go below 0
                if (! $adversaryAbsent)
                {
                    $thrust = $totalThrust;
                    if ($endingChallengerThreat + $thrust < 0)
                        $thrust = -$endingChallengerThreat;
                    $endingChallengerThreat += $thrust;
                }
            }

            // Store the computed values in results (technique's contribution is already included in totals)
            $results = [];
            $results['endingChallengerThreatBefore'] = $result['starting_challenger_threat'];
            $results['endingDefenderThreatBefore'] = $result['starting_defender_threat'];
            $results['challengerThreatIsLethal'] = $challengerThreatIsLethal;
            $results['defenderThreatIsLethal'] = $defenderThreatIsLethal;
            $results['riposte'] = $eventRiposte;
            $results['parry'] = $eventParry;
            $results['thrust'] = $eventThrust;
            $results['endingChallengerThreatAfter'] = $endingChallengerThreat;
            $results['endingDefenderThreatAfter'] = $endingDefenderThreat;
            
            $wounds = ($actorId == $challengerId) ? $endingChallengerThreat : $endingDefenderThreat;
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
        else
        {
            $endingChallengerThreat = $result['ending_challenger_threat'];
            $endingDefenderThreat = $result['ending_defender_threat'];
        }

        $results = [];
        $results['endingChallengerThreatBefore'] = $endingChallengerThreat;
        $results['endingDefenderThreatBefore'] = $endingDefenderThreat;
        $results['challengerThreatIsLethal'] = $challengerThreatIsLethal;
        $results['defenderThreatIsLethal'] = $defenderThreatIsLethal;

        if ($actorId == $challengerId)
        {
            //Riposte sends threat back to adversary, only in the amount it reduced threat to the actor
            // If adversary is absent (discard/locker), the bounced threat is discarded instead.
            $riposte = $eventRiposte;
            if ($riposte > $endingChallengerThreat) 
                $riposte = $endingChallengerThreat;
            $endingChallengerThreat -= $riposte;
            if (! $adversaryAbsent)
                $endingDefenderThreat += $riposte;
            $results['riposte'] = $eventRiposte;

            //Parry reduces threat
            $parry = $eventParry;
            if ($parry > $endingChallengerThreat) 
                $parry = $endingChallengerThreat;
            $endingChallengerThreat -= $parry;
            $results['parry'] = $eventParry;

            //Thrust adds threat — clamp so opponent threat cannot go below 0
            // WHY: negative thrust is intentional, but must not drive threat negative
            // Absent adversary: discard thrust (same as Riposte bounce).
            if (! $adversaryAbsent)
            {
                $thrust = $eventThrust;
                if ($endingDefenderThreat + $thrust < 0)
                    $thrust = -$endingDefenderThreat;
                $endingDefenderThreat += $thrust;
            }
            $results['thrust'] = $eventThrust;

            $wounds = $endingChallengerThreat;
        }
        else if ($actorId == $defenderId)
        {
            //Riposte sends threat back to adversary, only in the amount it reduced threat to the actor
            // If adversary is absent (discard/locker), the bounced threat is discarded instead.
            $riposte = $eventRiposte;
            if ($riposte > $endingDefenderThreat) 
                $riposte = $endingDefenderThreat;
            $endingDefenderThreat -= $riposte;
            if (! $adversaryAbsent)
                $endingChallengerThreat += $riposte;
            $results['riposte'] = $eventRiposte;
         
            //Parry reduces threat
            $parry = $eventParry;
            if ($parry > $endingDefenderThreat) 
                $parry = $endingDefenderThreat;
            $endingDefenderThreat -= $parry;
            $results['parry'] = $eventParry;

            //Thrust adds threat — clamp so opponent threat cannot go below 0
            if (! $adversaryAbsent)
            {
                $thrust = $eventThrust;
                if ($endingChallengerThreat + $thrust < 0)
                    $thrust = -$endingChallengerThreat;
                $endingChallengerThreat += $thrust;
            }
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