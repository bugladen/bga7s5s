<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01075 extends AttachmentAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Pressure Location with Influence");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }
        
        $owner = $this->getOwningCharacter($theah);

        if ($owner->Engaged)
        {
            return false;
        }

        if (! $theah->cardInCity($owner))
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId === $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCharacter($event->theah);

            $game->globals->set(Game::PRESSURING_PLAYER, $owner->ControllerId);
            $game->globals->set(Game::CHOSEN_PERFORMER, $owner->Id);
            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
            $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::TABARD_PRESSURE_TYPE);

            $this->announceAction($game);

            $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $owner->Id);
            $event->theah->queueEvent($engageEvent);

            $pressureStats = $event->theah->getPressureStats($owner, Game::STAT_INFLUENCE);
            $pressureOccuringEvent = EventFactory::createPressureOccuringEvent($owner->ControllerId, $owner->Id, $owner->Location, $pressureStats);
            $event->theah->queueEvent($pressureOccuringEvent);

            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "pressureLocation", $this->Id);
            $event->theah->queueEvent($transitionEvent);

            $this->setUsed($event->theah, true);
            $this->resetPlayerPassCount($game);
        }

        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id && $event->success)
        {
            $performer = $this->getOwningCharacter($event->theah);
            $claimEvent = EventFactory::createLocationClaimedEvent($performer->ControllerId, $performer->Id, $performer->Location);
            $event->theah->queueEvent($claimEvent);            
        }

    }
}