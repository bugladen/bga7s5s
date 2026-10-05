<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_05Henry extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Wound the adversary. Put Carlo into play at this location";
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        if (! $theah->game->globals->get(Game::IN_DUEL, false))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        if ($actor === null || ! $actor->hasTrait("Duelist"))
        {
            return false;
        }

        // WHY: Combat-card Maneuver — Carlo must still be on the dueling line as this
        // round's combat card. After resolve he leaves the line into play.
        // WHY no adversary-liveness gate: put-into-play remains useful if they already died.
        $owner = $this->getOwningCard($theah);
        if ($owner === null || $owner->Location != Game::LOCATION_DUELING_LINE)
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $actor = $event->theah->getDuelRoundActor();
            $adversary = $event->theah->getDuelRoundOpponent();

            // WHY: Printed order is Wound → Put into play. Queue wound first so FIFO
            // processes it before muster. Skip wound only when adversary is already gone.
            if ($adversary !== null && ! $event->theah->game->characterIsInDiscardOrLocker($adversary))
            {
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

            // WHY: "this location" during a duel = the actor's (duel) location.
            // Muster from dueling line → city/Home location; createCharacterMusteredEvent
            // handles ControllerId, world add, and client cardMustered notify.
            $musterEvent = EventFactory::createCharacterMusteredEvent(
                $owner->ControllerId,
                $owner->Id,
                $actor->Location
            );
            $event->theah->queueEvent($musterEvent);
        }
    }
}
