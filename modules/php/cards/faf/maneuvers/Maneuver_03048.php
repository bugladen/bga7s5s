<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_03048 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move all Threat to the Adversary");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        return $actor !== null && $actor->hasTrait("Scoundrel");
    }

    public function getManeuverFromCombatCardDiscount(Theah $theah, Card $combatCard, Array &$explanations): int
    {
        $discount = parent::getManeuverFromCombatCardDiscount($theah, $combatCard, $explanations);

        $owner = $this->getOwningCard($theah);
        if ($owner->Id == $combatCard->Id
            && $theah->game->globals->get(Game::DUEL_GAMBLED, false))
        {
            $discount += 1;
            $explanations[] = sprintf(
                $theah->game->translate("%s reduces the cost of Maneuver by 1 because this card was gambled."),
                $owner->getInjectCode()
            );
        }

        return $discount;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $actor = $event->theah->getDuelRoundActor();
            // WHY Riposte = current threat: DB riposte applies by moving threat from
            // actor to adversary (capped by actor threat). Same "clear threat via R/P/T"
            // shape as Technique_02012 (Parry for remove); Riposte moves it instead.
            $threat = $event->theah->getCurrentDuelThreat($actor->Id);
            if ($threat > 0)
            {
                $event->riposte += $threat;
                $event->explanations[] = sprintf(
                    $event->theah->game->translate("%s moves %d Threat from your participant to the adversary."),
                    $owner->getInjectCode(),
                    $threat
                );
            }
        }
    }
}
