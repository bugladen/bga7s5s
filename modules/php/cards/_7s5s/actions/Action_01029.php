<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
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

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCardById($performerId);

        if ($character->Id == $performer->Id)
        {
            return [false, $game->translate("Target character cannot be the same as the performer")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Target character is not at the same location as the performer")];
        }

        if ($character->ControllerId == $performer->ControllerId)
        {
            return [false, $game->translate("Target character cannot be controlled by the same player as the performer")];
        }

        $location = $game->theah->getCityLocation($character->Location);
        if ($location->Controller != $performer->ControllerId)
        {
            return [false, $game->translate("Player does not control the location of the target character")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void  
    { 
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01029)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new UserException($game->translate("Character not found"));
            }
    
            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
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