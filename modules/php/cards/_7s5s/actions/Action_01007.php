<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01007 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Renown from Location you control to his Location");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $locations = $theah->getCityLocations();
        $owner = $this->getOwningCharacter($theah);
        foreach ($locations as $location)
        {
            if ($location->Name == $owner->Location)
            {
                continue;
            }

            if ($location->Controller == $owner->ControllerId && $location->Reknown > 0)
            {
                return true;
            }
        }

        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01007", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array 
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01007)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args["performerId"] = $owner->Id;

            $locations = $game->theah->getCityLocations();
            $locations = array_filter($locations, fn($location) => $location->Controller == $owner->ControllerId && $location->Reknown > 0 && $location->Name != $owner->Location);
            $args["locationIds"] = array_map(fn($location) => $location->Name, array_values($locations));
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01007)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $location = $game->theah->getCityLocation($ids[0]);

            if ($location->Controller != $owner->ControllerId)
            {
                throw new \BgaUserException(sprintf($game->translate("You do not control %s."), $game->translate($location->Name)));
            }

            if ($location->Reknown == 0)
            {
                throw new \BgaUserException(sprintf($game->translate("%s does not have any Renown to move."), $location->Name));
            }

            $reknownRemovedEvent = EventFactory::createReknownRemovedFromLocationEvent($owner->ControllerId, $location->Name, 1, $owner->getInjectCode());
            $game->theah->queueEvent($reknownRemovedEvent);

            $reknownAddedEvent = EventFactory::createReknownAddedToLocationEvent($owner->ControllerId, $owner->Location, 1, $owner->getInjectCode(), $isMove = true);
            $game->theah->queueEvent($reknownAddedEvent);

            $this->announceAction($game);
            $this->resetPlayerPassCount($game);
            $this->setUsed($game->theah, true);

            $game->gamestate->nextState("locationChosen");
        }
    }
}