<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01172 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Move Target Character");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $performers = $theah->getCharactersInCityByPlayerId($playerId);
        $performers = array_values(array_filter($performers, fn($performer) => $performer->hasTrait('Sorcerer')));
        foreach ($performers as $performer)
        {
            $characters = $theah->getCharactersInCityByPlayerId($playerId);
            $characters = array_filter($characters, fn($character) => $character->Location != $performer->Location);
            if (count($characters) > 0)
            {
                return true;
            }
        }
        return false;        
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = $theah->getCharactersInCityByPlayerId($playerId);
        $performers = array_values(array_filter($performers, fn($performer) => $performer->hasTrait('Sorcerer')));
        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);
        
        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01172", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01172)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;

            $characters = $game->theah->getCharactersInCityByPlayerId($performer->ControllerId);
            $characters = array_filter($characters, fn($character) => $character->Location != $performer->Location);
            $characters = array_values(array_filter($characters, fn($character) => $character->Id != $performerId));
            $args["ids"] = array_map(fn($character) => $character->Id, $characters);
        }
        
        return $args;
    }
    
    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01172)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $target = $game->theah->getCharacterById($id);

            if ($target == null)
            {
                throw new \BgaUserException(sprintf($game->translate("Invalid target character id: %d"), $id));
            }

            if ($target->Id == $performer->Id)
            {
                throw new \BgaUserException($game->translate("Target character is the same as the performer."));
            }

            if ($target->Location == $performer->Location)
            {
                throw new \BgaUserException($game->translate("Target character is at the same location as the performer."));
            }
            
            $owner = $this->getOwningCard($game->theah);
            if (! $performer->hasTrait('Strega'))
            {
                $woundEvent = EventFactory::createCharacterWoundedEvent($performer->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($woundEvent);
            }

            $moveEvent = EventFactory::createCardMovedEvent($performer->ControllerId, $target->Id, $target->Location, $performer->Location, false, $performer->Id);
            $game->theah->queueEvent($moveEvent);

            $game->gamestate->nextState();
        }
    }
}
