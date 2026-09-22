<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_04048a extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 Riposte, Claim if Uncontrolled");
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
            $actor = $event->theah->getDuelRoundActor();
            // WHY: "this location" = duel site. Actor stays at the city location even if
            // the adversary was destroyed earlier in the round (same as Maneuver_01110).
            $location = $actor->Location;

            // WHY: Claim only when uncontrolled. canLocationBeClaimedBy does not require
            // controller == 0 — Indomitable Will can block while still uncontrolled.
            if ($event->theah->game->getControllerForLocation($location) != 0)
            {
                return;
            }

            if ($event->theah->canLocationBeClaimedBy($actor->ControllerId, $location))
            {
                $claimEvent = EventFactory::createLocationClaimedEvent(
                    $actor->ControllerId,
                    $actor->Id,
                    $location
                );
                $event->theah->queueEvent($claimEvent);
            }
            else
            {
                $event->theah->game->notify->all("message", clienttranslate('${location} cannot be claimed.'), [
                    'i18n' => ['location'],
                    'location' => $location,
                ]);
            }
        }
    }
}
