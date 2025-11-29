<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01030 extends RiskAction implements ISorcererAbility
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Pressure Location');
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $characters = $theah->getCharactersInCityWithOpposingCharacters($playerId);
        $characters = array_filter($characters, fn($character) => ! $character->Engaged);
        $characters = array_filter($characters, fn($character) => $character->hasTrait("Sorcerer") && $character->hasTrait("Strega"));

        return count($characters) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = $theah->getCharactersInCityWithOpposingCharacters($playerId);
        $performers = array_filter($performers, fn($character) => ! $character->Engaged);
        $performers = array_values(array_filter($performers, fn($character) => $character->hasTrait("Sorcerer") && $character->hasTrait("Strega")));
        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01030", $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id)
        {
            if ($event->success)
            {
                
                $claimEvent = EventFactory::createLocationClaimedEvent($event->playerId, $event->performerId, $event->location);
                $event->theah->queueEvent($claimEvent);            
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($event->playerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01030)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;

            $characters = $game->theah->getCharactersAtLocation($performer->Location);
            $characters = array_values(array_filter($characters, fn($character) => $character->isNotControlledByPlayer($performer->ControllerId)));
            $args["ids"] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01030)
        {
            $owner = $this->getOwningCard($game->theah);
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);            
            $character = $game->theah->getCharacterById($id);

            if ($character == null)
            {
                throw new \BgaUserException($game->translate("Character not found"));
            }

            if ($character->ControllerId == $owner->ControllerId)
            {
                throw new \BgaUserException($game->translate("You cannot manipulate your own character"));
            }

            if ($character->Location != $performer->Location)
            {
                throw new \BgaUserException($game->translate("Character is not at the same location as the Performer"));
            }

            $game->globals->set(Game::CHOSEN_TARGET, $character->Id);

            $game->notify->all("message", clienttranslate('${player_name} chose ${character_inject_code} as target for ${card_inject_code}.'), [
                'player_name' => $game->getPlayerNameById($performer->ControllerId),
                'character_inject_code' => $character->getInjectCode(),
                'card_inject_code' => $owner->getInjectCode(),
            ]);

            $event = EventFactory::createCardEngagedEvent($performer->ControllerId, $performer->Id, $owner->Id);
            $game->theah->queueEvent($event);

            $game->globals->set(Game::PRESSURING_PLAYER, $owner->ControllerId);
            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
            $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::PULL_THE_STRAND_PRESSURE_TYPE);

            $pressureStats = $game->theah->getPressureStats($performer, $performer->Location, Game::STAT_INFLUENCE);
            $pressureOccuringEvent = EventFactory::createPressureOccuringEvent($owner->ControllerId, $owner->Id, $performer->Location, $pressureStats);
            $game->theah->queueEvent($pressureOccuringEvent);

            $abilityPlayed = EventFactory::createSorcererAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $performer->Id, $character->Id, $character->Location);
            $game->theah->queueEvent($abilityPlayed);

            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "pressureLocation", $this->Id);
            $game->theah->queueEvent($transitionEvent);

            $game->gamestate->nextState();
        }
    }
}