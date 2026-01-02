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

class Action_01034 extends RiskAction implements IAbilityThatTargetsCards, IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('En Garde Performer');
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $performers = $theah->getCharactersinCityWithOpposingCharacters($playerId);
        $performers = array_values(array_filter($performers, fn($performer) => $performer->Engaged));

        $availablePerformers = [];
        foreach ($performers as $performer)
        {
            $opposingCharacters = $theah->getCharactersAtLocation($performer->Location);
            $opposingCharacters = array_filter($opposingCharacters, fn($character) => $character->isNotControlledByPlayer($playerId) && ! $character->Engaged);
            if (count($opposingCharacters) > 0)
            {
                $availablePerformers[] = $performer;
                break;
            }
        }

        return count($availablePerformers) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = $theah->getCharactersinCityWithOpposingCharacters($playerId);
        $performers = array_values(array_filter($performers, fn($character) => $character->Engaged));
        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01034", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01034)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;

            $characters = $game->theah->getCharactersAtLocation($performer->Location);
            $characters = array_values(array_filter($characters, fn($character) => $character->isNotControlledByPlayer($performer->ControllerId)));
            $characters = array_values(array_filter($characters, fn($character) => ! $character->Engaged));
            $args["ids"] = array_map(fn($character) => $character->Id, $characters);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01034_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $args["performerId"] = $performerId;

            $targetId = $game->globals->get(Game::CHOSEN_TARGET);
            $args["targetId"] = $targetId;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01034)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new \BgaUserException($game->translate("Character not found"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($character->ControllerId == $performer->ControllerId)
            {
                throw new \BgaUserException($game->translate("You cannot manipulate your own character"));
            }

            if ($character->Location != $performer->Location)
            {
                throw new \BgaUserException($game->translate("Character is not at the same location as the performer"));
            }

            $game->globals->set(Game::CHOSEN_TARGET, $character->Id);
            
            $owner = $this->getOwningCard($game->theah);
            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($performer->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $game->theah->queueEvent($woundEvent);

            $transitionEvent = EventFactory::createTransitionEvent($character->ControllerId, $owner->Id, "01034_2", $this->Id);
            $game->theah->queueEvent($transitionEvent);

            $game->gamestate->nextState();
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01034_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $targetId = $game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);

            $owner = $this->getOwningCard($game->theah);

            if ($id == 1)
            {
                $game->notify->all("message", clienttranslate('${player_name} decided to engage ${character_inject_code}'), [
                    'player_name' => $game->getPlayerNameById($performer->ControllerId),
                    'character_inject_code' => $target->getInjectCode(),
                ]);

                $engageEvent = EventFactory::createCardEngagedEvent($performer->ControllerId, $targetId, $owner->Id, $this->Id);
                $game->theah->queueEvent($engageEvent);

                $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
                $game->theah->queueEvent($actionResolvedEvent);

                $game->gamestate->nextState();
            }
        }
    }

    public function actFromActionPass(Game $game, int $state): void
    {
        parent::actFromActionPass($game, $state);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01034)
        {
            $game->gamestate->nextState();
        }
        
        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01034_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $targetId = $game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);

            $game->notify->all("message", clienttranslate('${player_name} chooses not to engage ${character_inject_code}'), [
                'player_name' => $game->getPlayerNameById($performer->ControllerId),
                'character_inject_code' => $target->getInjectCode(),
            ]);

            $owner = $this->getOwningCard($game->theah);
            $engardeEvent = EventFactory::createCardEngardedEvent($performer->ControllerId, $performer->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($engardeEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();

        }
    }
    

}
