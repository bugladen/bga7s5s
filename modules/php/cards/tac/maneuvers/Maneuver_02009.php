<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_02009 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();
        
        $this->Name = clienttranslate("Wound Adversary");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        return ($actor->hasTrait("Thug") || $actor->hasTrait("Duelist") || $actor->hasTrait("Spy"));
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $adversary = $event->theah->getDuelRoundOpponent();

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($adversary->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $event->theah->queueEvent($woundEvent);
        }
    }
}