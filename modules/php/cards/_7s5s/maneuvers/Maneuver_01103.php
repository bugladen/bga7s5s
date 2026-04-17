<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_01103 extends Maneuver
{
    private bool $UseParry;
    private bool $UseThrust;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+2 Riposte; and Choose +2 Parry or +2 Thrust");
        $this->UseParry = false;
        $this->UseThrust = false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01103", $this->Id);
            $transitionEvent->priority = Event::HIGH_PRIORITY;
            $event->theah->queueEvent($transitionEvent);
        }

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $event->riposte += 2;
            $event->explanations[] = sprintf($event->theah->game->translate("%s adds 2 Riposte."), $this->Name);
            if ($this->UseParry)
            {
                $event->parry += 2;
                $event->explanations[] = sprintf($event->theah->game->translate("%s adds 2 Parry."), $this->Name);
            }
            else if ($this->UseThrust)
            {
                $event->thrust += 2;
                $event->explanations[] = sprintf($event->theah->game->translate("%s adds 2 Thrust."), $this->Name);
            }
        }

        if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
        {
            $this->UseParry = false;
            $this->UseThrust = false;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelEndOfRound)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_DUELING_LINE)
            {
                $this->UseParry = false;
                $this->UseThrust = false;
                $owner = $this->getOwningCard($event->theah);
                $owner->IsUpdated = true;
            }
        }
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01103)
        {
            $owner = $this->getOwningCard($game->theah);
            // +2 Parry
            if ($id == 1)
            {
                $this->UseParry = true;
                $game->updateCardObjectInDb($owner);
            }
            // +2 Thrust
            else if ($id == 2)
            {
                $this->UseThrust = true;
                $game->updateCardObjectInDb($owner);
            }

            $game->gamestate->nextState();
        }
    }
}