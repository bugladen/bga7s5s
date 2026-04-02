<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01052 extends Maneuver
{
    private bool $HealAtEndOfRound;
    
    public function __construct()
    {
        parent::__construct();

        $this->HealAtEndOfRound = false;
        $this->Name = clienttranslate("Heal a Wound");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        
        return $actor->Wounds > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $this->HealAtEndOfRound = true;
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
        {
            $this->HealAtEndOfRound = false;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelEndOfRound)
        {
            $actor = $event->theah->getDuelRoundActor();
            if ($this->HealAtEndOfRound)
            {
                $owner = $this->getOwningCard($event->theah);
                $game = $event->theah->game;
                if (! $game->characterIsInDiscardOrLocker($actor) && $actor->Location != Game::LOCATION_HAND && $actor->Wounds > 0)
                {
                    $healEvent = EventFactory::createCharacterBeingHealedEvent($actor->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                    $event->theah->queueEvent($healEvent);
                }

                $this->HealAtEndOfRound = false;
                $owner->IsUpdated = true;
            }
        }
    }
}