<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01152b extends SchemeCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Performer, Engage Character");
        $this->RequiresPerformerSelected = true;
    }

    private function getOpposingCharacters(int $playerId, Theah $theah): array
    {
        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        $performers = [];
        foreach ($characters as $character)
        {
            $opposingCharacters = $theah->getCharactersAtLocation($character->Location);
            $opposingCharacters = array_values(array_filter($opposingCharacters, fn($character) => $character->isNotControlledByPlayer($playerId) && ! $character->Engaged));
            if (count($opposingCharacters) > 0)
            {
                $performers[] = $character;
                break;
            }
        }

        return $performers;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $characters = $this->getOpposingCharacters($playerId, $theah);

        return count($characters) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getOpposingCharacters($playerId, $theah);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01152b", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01152b)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $args['performerId'] = $performerId;
    
            $performer = $game->theah->getCharacterById($performerId);
            $opponents = $game->theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
            $args['characterIds'] = array_map(fn($character) => $character->Id, $opponents);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01152b)
        {
            $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new \BgaUserException($game->translate("Character not found"));
            }

            if ($target->Engaged)
            {
                throw new \BgaUserException($game->translate("Character is already engaged"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($target->Location != $performer->Location)
            {
                throw new \BgaUserException($game->translate("Character is not at the same location as the Performer"));
            }

            $scheme = $this->getOwningCard($game->theah);

            $event = EventFactory::createCharacterWoundedEvent($performer->Id, $scheme->Id, 1, $scheme->getInjectCode(), $this->Id);
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);

            $event = EventFactory::createCardEngagedEvent($performer->ControllerId, $target->Id, $scheme->Id);
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);

            $this->announceAction($game);

            $this->setUsed($game->theah, true);
            $this->resetPlayerPassCount($game);

            $game->gamestate->nextState("targetChosen");
        }
    }
}