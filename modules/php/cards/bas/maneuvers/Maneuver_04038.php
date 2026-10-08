<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_04038 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("En Garde Your Participant; Heal a Wound");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        // WHY Academic only: printed text has no cost before the effect (no •).
        // En garde / heal may noop if already en garde and unwounded — still playable.
        return $actor !== null && $actor->hasTrait("Academic");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $actor = $event->theah->getDuelRoundActor();
            if ($actor === null)
            {
                return;
            }

            if ($actor->Engaged)
            {
                $engardeEvent = EventFactory::createCardEngardedEvent(
                    $actor->ControllerId,
                    $actor->Id,
                    $owner->Id,
                    $this->Id
                );
                $event->theah->queueEvent($engardeEvent);
            }

            if ($actor->Wounds > 0)
            {
                $healEvent = EventFactory::createCharacterBeingHealedEvent(
                    $actor->Id,
                    $owner->Id,
                    1,
                    $owner->getInjectCode(),
                    $this->Id
                );
                $event->theah->queueEvent($healEvent);
            }
        }
    }
}
