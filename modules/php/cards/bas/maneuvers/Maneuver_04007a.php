<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_04007a extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("-3 Thrust, +2 Riposte");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        return $actor !== null && $actor->hasTrait('Duelist');
    }

    // WHY: Discount lives only on a — Card::getManeuverFromCombatCardDiscount sums every
    // Maneuver on the Risk. Putting it on both a and b would double-count to -2.
    public function getManeuverFromCombatCardDiscount(Theah $theah, Card $combatCard, Array &$explanations): int
    {
        $discount = parent::getManeuverFromCombatCardDiscount($theah, $combatCard, $explanations);

        $owner = $this->getOwningCard($theah);
        if ($owner->Id == $combatCard->Id)
        {
            $actor = $theah->getDuelRoundActor();
            $adversary = $theah->getDuelRoundOpponent();
            if ($actor !== null && $adversary !== null
                && $adversary->Wounds > $actor->Wounds)
            {
                $discount += 1;
                $explanations[] = sprintf(
                    $theah->game->translate("%s reduces the cost of Maneuver by 1 because the adversary has more wounds than your participant."),
                    $owner->getInjectCode()
                );
            }
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
            $event->thrust -= 3;
            $event->riposte += 2;
            $event->explanations[] = sprintf(
                $event->theah->game->translate("%s subtracts 3 Thrust and adds 2 Riposte."),
                $owner->getInjectCode()
            );
        }
    }
}
