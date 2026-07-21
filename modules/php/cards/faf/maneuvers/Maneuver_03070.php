<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_03070 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Discard excess Threat");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
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
            // Game::CHALLENGE_STAT, not a free-choice or printed-only Combat default.
            $combatStatUsed = $event->theah->game->globals->get(Game::CHALLENGE_STAT);
            $stat = match ($combatStatUsed) {
                Game::STAT_FINESSE => $adversary->ModifiedFinesse,
                Game::STAT_INFLUENCE => $adversary->ModifiedInfluence,
                default => $adversary->ModifiedCombat,
            };

            // WHY Parry (not Riposte): Pattern C.6 — "discard/remove" threat subtracts
            // without moving it to the adversary (Technique_02012). Excess only, not all.
            $threat = $event->theah->getCurrentDuelThreat($actor->Id);
            $excess = max(0, $threat - $stat);
            if ($excess > 0)
            {
                $event->parry += $excess;
                $event->explanations[] = sprintf(
                    $event->theah->game->translate("%s discards %d Threat in excess of the adversary's duel-stat value (%d)."),
                    $owner->getInjectCode(),
                    $excess,
                    $stat
                );
            }
        }
    }
}
