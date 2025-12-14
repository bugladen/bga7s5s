<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01041 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Manipulate Opposing Non-Leader Character");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $rosine = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($rosine))
        {
            return false;
        }

        $characters = $theah->getCharactersAtLocation($rosine->Location);
        $characters = array_filter($characters, fn($character) => 
            $character->isNotControlledByPlayer($playerId) && 
            ! $character->hasTrait("Leader") && 
            $character->ModifiedInfluence <= $rosine->ModifiedInfluence);

        return count($characters) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01041", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01041)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args["performerId"] = $owner->Id;

            $characters = $game->theah->getCharactersAtLocation($owner->Location);
            $characters = array_filter($characters, fn($character) => 
                $character->isNotControlledByPlayer($owner->ControllerId) && 
                ! $character->hasTrait("Leader") && 
                $character->ModifiedInfluence <= $owner->ModifiedInfluence);
            $args["ids"] = array_map(fn($character) => $character->Id, array_values($characters));
        }

        return $args;
    }
    
    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01041)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new \BgaUserException($game->translate("Character not found"));
            }

            $owner = $this->getOwningCharacter($game->theah);
            if ($character->ControllerId == $owner->ControllerId)
            {
                throw new \BgaUserException($game->translate("You cannot manipulate your own character"));
            }
            
            if ($character->Location != $owner->Location)
            {
                throw new \BgaUserException($game->translate("Character is not at the same location as the performer"));
            }

            if ($character->hasTrait("Leader"))
            {
                throw new \BgaUserException($game->translate("Character is a leader"));
            }

            if ($character->ModifiedInfluence > $owner->ModifiedInfluence)
            {
                throw new \BgaUserException($game->translate("Character has more influence than the performer"));
            }

            $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $character->Id, $owner->Id);
            $game->theah->queueEvent($engageEvent);

            if ($character->hasTrait("Sorcerer"))
            {
                $moveEvent = EventFactory::createCardMovedEvent($owner->ControllerId, $character->Id, $character->Location, Game::LOCATION_PLAYER_HOME, false, $owner->Id);
                $game->theah->queueEvent($moveEvent);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $this->announceAction($game);
            $this->setUsed($game->theah, true);
            $this->resetPlayerPassCount($game);

            $game->gamestate->nextState("characterChosen");
        }
    }
}