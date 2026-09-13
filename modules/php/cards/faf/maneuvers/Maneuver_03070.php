<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_03070 extends Maneuver
{
    // WHY public: Maneuver instance persists on the Risk across serialize/DB; cancel
    // after ThreatModified must restore the same excess that was discarded.
    public int $ExcessDiscarded = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Discard excess Threat");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $actor = $event->theah->getDuelRoundActor();
            $adversary = $event->theah->getDuelRoundOpponent();
            if ($actor === null || $adversary === null)
            {
                return;
            }

            // WHY same CHALLENGE_STAT match as Restricted Hostilities (stDuelEndOfRound):
            // printed example is "adversary's [Influence]" in an Influence duel — that is
            // Game::CHALLENGE_STAT, not actor ModifiedCombat or a free-choice button.
            $combatStatUsed = $event->theah->game->globals->get(Game::CHALLENGE_STAT);
            $stat = match ($combatStatUsed) {
                Game::STAT_FINESSE => $adversary->ModifiedFinesse,
                Game::STAT_INFLUENCE => $adversary->ModifiedInfluence,
                default => $adversary->ModifiedCombat,
            };

            // WHY starting_* not ending_*: discard is against original round-start threat.
            // Combat/Technique may already be applied to ending_*; reducing starting and
            // letting the following Calculate rebuild reapplies stored R/P/T against the
            // new baseline (combat mode itself is incremental and will not redo).
            $starting = $event->theah->getStartingDuelThreat($actor->Id);
            $excess = max(0, $starting - $stat);
            if ($excess <= 0)
            {
                return;
            }

            $challengerId = $event->theah->getDuelChallengerId();
            $challengerDelta = $actor->Id == $challengerId ? -$excess : 0;
            $defenderDelta = $actor->Id == $challengerId ? 0 : -$excess;

            $this->ExcessDiscarded = $excess;
            $owner->IsUpdated = true;

            // WHY stackEvent: Resolve→Calculate are already queued; ThreatModified must
            // land between them so Calculate's rebuild sees the reduced starting_*.
            // Do not add Maneuver Parry — that would double-count on the lowered baseline.
            $threatEvent = EventFactory::createThreatModifiedEvent($challengerDelta, $defenderDelta);
            $event->theah->stackEvent($threatEvent);

            $newStarting = $starting - $excess;
            $event->theah->game->notify->all("message", sprintf(
                $event->theah->game->translate("%s discards %d Threat from the round's starting pool (excess over the adversary's duel-stat value %d). Starting Threat goes from %d to %d. Technique and combat-card stats will recompute."),
                $owner->getInjectCode(),
                $excess,
                $stat,
                $starting,
                $newStarting
            ), []);
        }

        if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
        {
            if ($this->ExcessDiscarded > 0)
            {
                $actor = $event->theah->getDuelRoundActor();
                if ($actor !== null)
                {
                    $challengerId = $event->theah->getDuelChallengerId();
                    $challengerDelta = $actor->Id == $challengerId ? $this->ExcessDiscarded : 0;
                    $defenderDelta = $actor->Id == $challengerId ? 0 : $this->ExcessDiscarded;

                    // WHY starting-only restore + rebuild: plain ThreatModified(+excess)
                    // would add the delta to ending after clamp-sensitive combat apply and
                    // leave the wrong pool. Mirror resolve: fix starting, reapply R/P/T.
                    $duelId = $event->theah->game->globals->get(Game::DUEL_ID);
                    $round = $event->theah->game->globals->get(Game::DUEL_ROUND);
                    $db = $event->theah->getDBObject();
                    $db->executeSql(
                        "UPDATE duel_round SET
                            starting_challenger_threat = starting_challenger_threat + {$challengerDelta},
                            starting_defender_threat = starting_defender_threat + {$defenderDelta}
                         WHERE duel_id = {$duelId} AND round = {$round}"
                    );
                    $event->theah->rebuildDuelRoundEndingThreats();

                    $result = $db->getRoundThreats($duelId, $round);
                    $event->theah->game->notify->all("updateRoundThreats", clienttranslate(
                        'Threat for the round has been restored after Maneuver cancel.'), [
                        "starting_challenger_threat" => $result['starting_challenger_threat'],
                        "starting_defender_threat" => $result['starting_defender_threat'],
                        "challenger_threat" => $result['ending_challenger_threat'],
                        "defender_threat" => $result['ending_defender_threat'],
                        "challengerThreatIsLethal" => $result['challenger_threat_is_lethal'],
                        "defenderThreatIsLethal" => $result['defender_threat_is_lethal'],
                        "wounds" => $result['wounds_taken'],
                        "round" => $round,
                        "challenger_modification" => $challengerDelta,
                        "defender_modification" => $defenderDelta,
                        "challenger_inject_code" => '',
                        "defender_inject_code" => '',
                        "challenger_lethal_text" => '',
                        "defender_lethal_text" => '',
                    ]);
                }

                $this->ExcessDiscarded = 0;
                $owner = $this->getOwningCard($event->theah);
                $owner->IsUpdated = true;
            }
        }

        if ($event instanceof EventDuelNewRound && $this->ExcessDiscarded != 0)
        {
            $this->ExcessDiscarded = 0;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }
    }
}
