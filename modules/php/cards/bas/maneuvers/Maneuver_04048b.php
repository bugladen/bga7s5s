<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_04048b extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 Riposte, Unclaim if Controlled");
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

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->riposte += 1;
            $event->explanations[] = sprintf(
                $event->theah->game->translate("%s adds 1 Riposte."),
                $owner->getInjectCode()
            );
        }

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $actor = $event->theah->getDuelRoundActor();
            // WHY: "this location" = duel site (actor location), not adversary Location
            // which can be Locker-* after destruction — see Maneuver_01110.
            $location = $actor->Location;

            // WHY: Unclaim only when controlled. canLocationBecomeUncontrolledBy does not
            // require a current controller — check explicitly so Home / uncontrolled City
            // skip silently (printed If clause).
            if ($event->theah->game->getControllerForLocation($location) == 0)
            {
                return;
            }

            if ($event->theah->canLocationBecomeUncontrolledBy($owner->ControllerId, $location))
            {
                $uncontrolledEvent = EventFactory::createLocationBecomesUncontrolledEvent(
                    $owner->ControllerId,
                    $location
                );
                $event->theah->queueEvent($uncontrolledEvent);
            }
            else
            {
                $event->theah->game->notify->all("message", clienttranslate('${location} cannot become uncontrolled.'), [
                    'i18n' => ['location'],
                    'location' => $location,
                ]);
            }
        }
    }
}
