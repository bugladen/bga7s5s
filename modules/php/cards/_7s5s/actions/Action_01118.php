<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01118 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Elina to Adjacent Location");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $elina = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($elina))
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01118", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01118)
        {

            $elina = $this->getOwningCharacter($game->theah);
            $args["performerId"] = $elina->Id;

            $locations = $game->theah->getAdjacentCityLocations($elina->Location, $includeHome = false);
            $args["locationIds"] = $locations;

        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01118)
        {
            $elina = $this->getOwningCharacter($game->theah);
            $location = $ids[0];

            $locations = $game->theah->getAdjacentCityLocations($elina->Location, $includeHome = false);
            if ( ! in_array($location, $locations))
            {
                throw new \BgaUserException(sprintf($game->translate("Location %s is not adjacent to Location %s."), $location->Name, $elina->Location));
            }

            $moveEvent = EventFactory::createCardMovedEvent($elina->ControllerId, $elina->Id, $elina->Location, $location);
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $characters = $game->theah->getCharactersAtLocation($location);
            $characters = array_filter($characters, fn($character) => $character->isNotControlledByPlayer($elina->ControllerId));
            if (count($characters) > 0)
            {
                $engardeEvent = EventFactory::createCardEngardedEvent($elina->ControllerId, $elina->Id, $elina->Id);
                $game->theah->queueEvent($engardeEvent);
            }

            $this->announceAction($game);
            $this->resetPlayerPassCount($game);
            $this->setUsed($game->theah, true);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($elina->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }
}