<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_05Glass extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Wound the Adversary if they have more Influence";
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        $adversary = $theah->getDuelRoundOpponent();
        if ($actor === null || $adversary === null)
        {
            return false;
        }

        // WHY: Wound is the only payoff — hide when adversary already destroyed.
        if ($theah->game->characterIsInDiscardOrLocker($adversary))
        {
            return false;
        }

        // WHY: Printed "adversary has more Influence than your participant" → strict >.
        // Bullet-If is availability (Maneuver_03033 / Maneuver_01033 discipline), not resolve no-op.
        return $adversary->ModifiedInfluence > $actor->ModifiedInfluence;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $adversary = $event->theah->getDuelRoundOpponent();

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $adversary->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $event->theah->eventCheck($woundEvent);
            $event->theah->queueEvent($woundEvent);
        }
    }
}
