<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01096 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Move to Location with Equipped Enemy");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        $locations = $theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
        $availableCharacters = [];
        foreach ($locations as $location)
        {
            $characters = $theah->getOpposingCharactersAtLocation($location, $playerId);
            foreach ($characters as $character)
            {
                if ($character->isControlled() && count($character->Attachments) > 0)
                {
                    $availableCharacters[] = $character;
                }
            }
        }
        return count($availableCharacters) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01096", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01096)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args['performerId'] = $performerId;

            $locations = $game->theah->getAdjacentCityLocations($performer->Location, $includeHome = false);
            $availableCharacters = [];
            foreach ($locations as $location)
            {
                $characters = $game->theah->getOpposingCharactersAtLocation($location, $performer->ControllerId);
                foreach ($characters as $character)
                {
                    if ($character->isControlled() && count($character->Attachments) > 0)
                    {
                        $availableCharacters[] = $character;
                    }
                }
            }

            $args['ids'] = array_map(fn($character) => $character->Id, $availableCharacters);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01096)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new \BgaUserException($game->translate("Character not found"));
            }

            if ($character->ControllerId == $owner->ControllerId)
            {
                throw new \BgaUserException($game->translate("You cannot manipulate your own character"));
            }

            $locations = $game->theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
            if (!in_array($character->Location, $locations))
            {
                throw new \BgaUserException(sprintf($game->translate("Location %s is not adjacent to %s"), $character->Location, $owner->Location));
            }

            $event = EventFactory::createCardMovedEvent($owner->ControllerId, $owner->Id, $owner->Location, $character->Location, $engage = false, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $this->announceAction($game);
            $this->resetPlayerPassCount($game);
            $this->setUsed($game->theah, true);

            $game->gamestate->nextState("characterChosen");
        }
    }
}