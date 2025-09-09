<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01205 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Kidnap Character");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $giacinto = $this->getOwningCharacter($theah);

        if (! $giacinto->isControlled())
        {
            return false;
        }

        if (! $theah->cardInCity($giacinto))
        {
            return false;
        }

        if ( $giacinto->Engaged )
        {
            return false;
        }

        $characters = $theah->getCharactersAtLocation($giacinto->Location);
        $characters = array_filter($characters, fn($c) => $c->ControllerId != $giacinto->ControllerId);
        
        return count($characters) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01205", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array 
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01205)
        {
            $giacinto = $this->getOwningCharacter($game->theah);
            $characters = $game->theah->getCharactersAtLocation($giacinto->Location);
            $characters = array_values(array_filter($characters, fn($c) => $c->ControllerId != $giacinto->ControllerId));

            $args["characterId"] = $giacinto->Id;
            $args["targetCharacterIds"] = array_map(fn($c) => $c->Id, $characters);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01205_2)
        {
            $giacinto = $this->getOwningCharacter($game->theah);
            $locations = $game->theah->getAdjacentCityLocations($giacinto->Location, $includeHome = false);

            $victimId = $game->globals->get(Game::CHOSEN_CARD);

            $args["characterId"] = $giacinto->Id;
            $args["victimId"] = $victimId;
            
            $args["locations"] = $locations;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void  
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01205)
        {
            $giacinto = $this->getOwningCharacter($game->theah);

            $targetCharacter = $game->theah->getCharacterById($id);
            if ($targetCharacter == null)
            {
                throw new \BgaUserException(sprintf($game->translate("Invalid target character id: %d"), $id));
            }

            if ($targetCharacter->ControllerId == $giacinto->ControllerId)
            {
                throw new \BgaUserException($game->translate("Target character cannot be the same as Giacinto."));
            }

            if ($targetCharacter->Location != $giacinto->Location)
            {
                throw new \BgaUserException($game->translate("Target character is not at Giacinto's location."));
            }

            $game->globals->set(Game::CHOSEN_CARD, $targetCharacter->Id);

            $game->gamestate->nextState("victimChosen");
        }
}

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void  
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01205_2)
        {
            $location = $game->theah->getCityLocation($ids[0]);

            $giacinto = $this->getOwningCharacter($game->theah);
            $victimId = $game->globals->get(Game::CHOSEN_CARD);
            $victim = $game->theah->getCharacterById($victimId);
    
            $locations = $game->theah->getAdjacentCityLocations($giacinto->Location, $includeHome = false);
            if ( ! in_array($location->Name, $locations))
            {
                throw new \BgaUserException(sprintf($game->translate("Location %s is not adjacent to Location %s."), $location->Name, $giacinto->Location));
            }

            $giacintoEngageEvent = EventFactory::createCardEngagedEvent($giacinto->ControllerId, $giacinto->Id);
            $game->theah->eventCheck($giacintoEngageEvent);

            if ( ! $victim->Engaged)
            {
                $victimEngageEvent = EventFactory::createCardEngagedEvent($giacinto->ControllerId, $victim->Id);
                $game->theah->eventCheck($victimEngageEvent);
            }

            $giacintoMoveEvent = EventFactory::createCardMovedEvent($giacinto->ControllerId, $giacinto->Id, $giacinto->Location, $location->Name);
            $game->theah->eventCheck($giacintoMoveEvent);

            $victimMoveEvent = EventFactory::createCardMovedEvent($giacinto->ControllerId, $victim->Id, $victim->Location, $location->Name, true, $giacinto->Id);
            $game->theah->eventCheck($victimMoveEvent);

            $game->theah->queueEvent($giacintoEngageEvent);

            if ( ! $victim->Engaged)            
                $game->theah->queueEvent($victimEngageEvent);

            $game->theah->queueEvent($giacintoMoveEvent);
            $game->theah->queueEvent($victimMoveEvent);

            $game->notifyAllPlayers("message", clienttranslate('${player_name} has used the [${action}] Action from ${owner_inject_code}'), [
                'i18n' => ['action'],
                'player_name' => $game->getActivePlayerName(),
                'action' => $this->Name,
                'owner_inject_code' => $giacinto->getInjectCode(),
            ]);

            $this->setUsed($game->theah, true);
            $this->resetPlayerPassCount($game);

            $game->gamestate->nextState("locationChosen");

        }
    }
}