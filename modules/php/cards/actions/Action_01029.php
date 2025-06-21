<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01029 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();
        
        $this->Name = "Engage Character";
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $performersWithOpposingCharacters = $this->getOpposingCharacters($theah, $playerId);

        return count($performersWithOpposingCharacters) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getOpposingCharacters($theah, $playerId);
    }

    private function getOpposingCharacters(Theah $theah, int $playerId): array
    {
        // Add characters owned by the player that are in play
        $performers = $theah->getCharactersInPlayByPlayerId($playerId);

        //Filter out any characters that are not in the city
        $performers = array_filter($performers, fn($character) => $theah->cardInCity($character));

        //Filter out any chracters that are not at a location controlled by the player
        $controllingPerformers = [];
        $controllers = $theah->getCityLocationControllers();
        foreach ($performers as $performer)
            if ($controllers[$performer->Location] == $playerId)
                $controllingPerformers[] = $performer;
        
        //Get opposing characters that are not engaged
        $performersWithOpposingCharacters = [];
        foreach ($controllingPerformers as $performer)
        {
            $characters = $theah->getCharactersAtLocation($performer->Location);
            foreach ($characters as $character)
            {
                if ($character->isControlled() && $character->ControllerId != $performer->ControllerId && ! $character->Engaged)
                {
                    $performersWithOpposingCharacters[] = $performer;
                    break;
                }
            }

        }

        return $performersWithOpposingCharacters;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);
        
        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01029");
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array 
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCardById($performerId);

        $characters = $game->theah->getCharactersAtLocation($performer->Location);
        //Filter out characters owned by the player
        $characters = array_values(array_filter($characters, fn($character) => $character->ControllerId != $performer->ControllerId));
        $args['ids'] = array_map(fn($character) => $character->Id, $characters);

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void  
    { 
        parent::actFromActionWithId($game, $state, $stateName, $id);

        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCardById($performerId);

        $characters = $game->theah->getCharactersAtLocation($performer->Location);
        //Filter out characters owned by the player
        $characters = array_filter($characters, fn($character) => $character->ControllerId != $performer->ControllerId);

        $character_ids = array_map(fn($character) => $character->Id, $characters);

        if ( ! in_array($id, $character_ids))
        {
            throw new \BgaUserException($game->translate("Invalid character selected"));
        }

        $owner = $this->getOwningCard($game->theah);

        $target = $game->theah->getCardById($id);
        $event = EventFactory::createCardEngagedEvent($game->getActivePlayerId(), $target->Id, $owner->Id);
        $game->theah->queueEvent($event);

        $game->gamestate->nextState("cardChosen");
        
    }
}