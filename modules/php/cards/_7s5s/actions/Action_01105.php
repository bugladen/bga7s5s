<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01105 extends RiskCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();
        
        $this->Name = clienttranslate("Pressure Location with Resolve. Engage Character.");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        return count($characters) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $theah->getCharactersInCityByPlayerId($playerId);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $game->globals->set(Game::PRESSURING_PLAYER, $performer->ControllerId);
            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
            $game->globals->set(Game::PRESSURE_STAT, Game::STAT_RESOLVE);

            $pressureStats = $event->theah->getPressureStats($performer, $performer->Location, Game::STAT_RESOLVE);
            $pressureOccuringEvent = EventFactory::createPressureOccuringEvent($performer->ControllerId, $performer->Id, $performer->Location, $pressureStats);
            $game->theah->queueEvent($pressureOccuringEvent);

            //Go straight to stHighDramaPressureLocation
            $transitionEvent = EventFactory::createTransitionEvent($performer->ControllerId, $performer->Id, "pressureLocation", $this->Id);
            $event->theah->queueEvent($transitionEvent);

        }
    
        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id && $event->success)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01105", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }

        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $theah = $event->theah;
            $owner = $this->getOwningCard($theah);
            $character = $theah->getCharacterById($event->targetId);
            // WHY CHOSEN_PERFORMER: still set while the targeting event dispatches.
            $performer = $theah->getCharacterById((int)$theah->game->globals->get(Game::CHOSEN_PERFORMER));
            if ($owner === null || $character === null || $performer === null)
            {
                return;
            }

            $engageEvent = EventFactory::createCardEngagedEvent($performer->ControllerId, $character->Id, $owner->Id, $this->Id);
            $engageEvent->batchId = $event->batchId ?? $theah->game->getNextEventBatchId();
            $theah->queueEvent($engageEvent);

            // ActionResolved stays unbatched (01160 convention) so a batch strip cannot eat it.
            $theah->queueEvent(EventFactory::createActionResolvedEvent($performer->ControllerId));
        }
    }

    public function actFromActionPass(Game $game, int $state): void
    {
        parent::actFromActionPass($game, $state);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01105)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $game->notify->all("message", clienttranslate('${player_name} chooses not to Engage any character.'), [
                "player_name" => $game->getPlayerNameById($performer->ControllerId),
            ]);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01105)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args['performerId'] = $performerId;

            $characters = $game->theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
            $args['ids'] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Character is not at the same location as the performer")];
        }

        if ($character->Engaged)
        {
            return [false, $game->translate("Character is already engaged.")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01105)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            // WHY: Fire targeting alone here. The engage + ActionResolved emit from handleEvent
            // only when EventCharacterTargeted survives (Cloud Bottle / UL cancel it). The
            // pressure already resolved (its own event chain) and is not part of the targeting.
            $owner = $this->getOwningCard($game->theah);
            $targetedEvent = EventFactory::createCharacterTargetedEvent($performer->ControllerId, $character->Id, $owner->Id, $this->Id);
            $targetedEvent->batchId = $game->getNextEventBatchId();
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState();
        }
    }
}
