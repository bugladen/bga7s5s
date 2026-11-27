<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01030 extends RiskAction implements ISorcererAbility, IAbilityThatTargetsCharacters
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

        $owner = $this->getOwningCard($theah);
        $characters = $theah->getCharactersInCityWithOpposingCharacters($playerId);
        $characters = array_filter($characters, fn($character) => ! $character->Engaged && $character->canPressure(Game::STAT_INFLUENCE));
        // WHY queryCard: Patricia/Uwe trait fiction only fires when the ability identifies itself.
        $characters = array_filter($characters, fn($character) => $character->hasTrait("Sorcerer", $owner) && $character->hasTrait("Strega", $owner));

        return count($characters) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $owner = $this->getOwningCard($theah);
        $performers = $theah->getCharactersInCityWithOpposingCharacters($playerId);
        $performers = array_filter($performers, fn($character) => ! $character->Engaged && $character->canPressure(Game::STAT_INFLUENCE));
        $performers = array_values(array_filter($performers, fn($character) => $character->hasTrait("Sorcerer", $owner) && $character->hasTrait("Strega", $owner)));
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

        // WHY: Engage performer + Sorcerer start + pressure stage only when targeting survives
        // (Cloud Bottle / UL cancel it). Engage is gated with the effect so a canceled hook
        // does not leave a pure cost (01015 seat). CHOSEN_TARGET is set here, not at the pick,
        // so a cancel leaves no stale target behind.
        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $performer = $event->theah->getCharacterById($game->globals->get(Game::CHOSEN_PERFORMER));
            $character = $event->theah->getCharacterById($event->targetId);
            if ($owner === null || $performer === null || $character === null)
            {
                return;
            }

            $batchId = $event->batchId ?? $game->getNextEventBatchId();
            $game->globals->set(Game::CHOSEN_TARGET, $character->Id);

            $engageEvent = EventFactory::createCardEngagedEvent($performer->ControllerId, $performer->Id, $owner->Id, $this->Id);
            $engageEvent->batchId = $batchId;
            $event->theah->queueEvent($engageEvent);

            $startEvent = EventFactory::createSorcererAbilityStartEvent($owner->ControllerId, $owner->Id, $this->Id, $performer->Id, $character->Id, $character->Location);
            $startEvent->batchId = $batchId;
            $event->theah->queueEvent($startEvent);

            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01030_2", $this->Id);
            $transitionEvent->batchId = $batchId;
            $event->theah->queueEvent($transitionEvent);
        }

        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id)
        {
            if ($event->success)
            {
                if ($event->theah->canLocationBeClaimedBy($event->playerId, $event->location))
                {
                    $claimEvent = EventFactory::createLocationClaimedEvent($event->playerId, $event->performerId, $event->location);
                    $event->theah->queueEvent($claimEvent);
                }
                else
                {
                    $event->theah->game->notify->all("message", clienttranslate('${location} cannot be claimed.'), [
                        'i18n' => ['location'],
                        'location' => $event->location,
                    ]);
                }
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

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);
        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Target character is not at the same location as the performer")];
        }
        if ($character->ControllerId == $performer->ControllerId)
        {
            return [false, $game->translate("Target character cannot be controlled by the same player as the performer")];
        }

        return [true, ""];
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
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }


            $game->notify->all("message", clienttranslate('${player_name} chose ${character_inject_code} as target for ${card_inject_code}.'), [
                'player_name' => $game->getPlayerNameById($performer->ControllerId),
                'character_inject_code' => $character->getInjectCode(),
                'card_inject_code' => $owner->getInjectCode(),
            ]);

            // WHY: Fire targeting alone here; engage / Sorcerer start / 01030_2 emit from
            // handleEvent only when EventCharacterTargeted is not canceled.
            $targetedEvent = EventFactory::createCharacterTargetedEvent($owner->ControllerId, $character->Id, $owner->Id, $this->Id);
            $targetedEvent->batchId = $game->getNextEventBatchId();
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState();
        }
    }

    public function stateFromAction(Game $game, int $state, string $stateName): void
    {
        parent::stateFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01030_2)
        {
            $owner = $this->getOwningCard($game->theah);

            $game->globals->set(Game::PRESSURING_PLAYER, $owner->ControllerId);
            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
            $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::PULL_THE_STRAND_PRESSURE_TYPE);

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);   
            
            $characterId = $game->globals->get(Game::CHOSEN_TARGET);
            $character = $game->theah->getCharacterById($characterId);

            $pressureStats = $game->theah->getPressureStats($performer, $performer->Location, Game::STAT_INFLUENCE);
            $pressureOccuringEvent = EventFactory::createPressureOccuringEvent($owner->ControllerId, $performer->Id, $performer->Location, $pressureStats);
            $game->theah->queueEvent($pressureOccuringEvent);

            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "pressureLocation", $this->Id);
            $game->theah->queueEvent($transitionEvent);

            $abilityPlayed = EventFactory::createSorcererAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $performer->Id, $character->Id, $character->Location);
            $abilityPlayed->priority = Event::TRANSITION_PRIORITY;
            $game->theah->queueEvent($abilityPlayed);

            $game->gamestate->nextState();
        }
    }
}