<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01029 extends RiskAction implements IAbilityThatTargetsCards, IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();
        
        $this->Name = clienttranslate("Engage Character");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
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
        $performers = $theah->getCharactersInCityByPlayerId($playerId);

        //Filter out any characters that are not at a location controlled by the player
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
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01029", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array 
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01029)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCardById($performerId);
    
            $characters = $game->theah->getCharactersAtLocation($performer->Location);
            $characters = array_values(array_filter($characters, fn($character) => $character->isNotControlledByPlayer($performer->ControllerId)));
            $args['ids'] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void  
    { 
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01029)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCardById($performerId);
    
            $characters = $game->theah->getCharactersAtLocation($performer->Location);
            $characters = array_values(array_filter($characters, fn($character) => $character->isNotControlledByPlayer($performer->ControllerId)));
    
            $character_ids = array_map(fn($character) => $character->Id, $characters);
    
            if ( ! in_array($id, $character_ids))
            {
                throw new \BgaUserException($game->translate("Invalid character selected"));
            }
    
            $owner = $this->getOwningCard($game->theah);
    
            $target = $game->theah->getCardById($id);
            $event = EventFactory::createCardEngagedEvent($game->getActivePlayerId(), $target->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }
}