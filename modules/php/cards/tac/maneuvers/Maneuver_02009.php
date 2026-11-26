<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_02009 extends Maneuver
{
    private string $RequiredTrait;

    public function __construct(string $requiredTrait, string $name)
    {
        parent::__construct();

        $this->RequiredTrait = $requiredTrait;
        $this->Name = $name;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        // WHY queryCard: Patricia/Uwe trait fiction only fires when the ability identifies itself.
        $owner = $this->getOwningCard($theah);
        $actor = $theah->getDuelRoundActor();
        return $actor->hasTrait($this->RequiredTrait, $owner);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $adversary = $event->theah->getDuelRoundOpponent();

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($adversary->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $event->theah->queueEvent($woundEvent);
        }
    }
}