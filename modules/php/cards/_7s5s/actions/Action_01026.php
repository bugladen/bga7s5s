<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01026 extends RiskAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Destroy Performer, Manipulate Character");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCard($theah);
        $performers = $theah->getCharactersInCityWithOpposingCharacters($playerId);
        // WHY queryCard: Patricia/Uwe trait fiction only fires when the ability identifies itself.
        $performers = array_filter($performers, fn($performer) => $performer->hasTrait("Red Hand", $owner));

        return count($performers) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $owner = $this->getOwningCard($theah);
        $performers = $theah->getCharactersInCityWithOpposingCharacters($playerId);
        $performers = array_values(array_filter($performers, fn($performer) => $performer->hasTrait("Red Hand", $owner)));
        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01026", $this->Id);
            $event->theah->queueEvent($transition);
        }

        // WHY: Manipulate (engage, or send Home if already engaged) only when
        // EventCharacterTargeted survives. Cloud Bottle / UL / Maryam cancel that hook.
        // Destroying the performer is the COST and stays in actFromActionWithId — it always
        // happens even if the targeting is canceled (a cost, not an effect).
        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $theah = $event->theah;
            $owner = $this->getOwningCard($theah);
            $character = $theah->getCharacterById($event->targetId);
            if ($owner === null || $character === null)
            {
                return;
            }

            $batchId = $event->batchId ?? $theah->game->getNextEventBatchId();

            // WHY: ControllerId from the Risk (performer may already be destroyed by now and
            // is not guaranteed to still resolve).
            if (! $character->Engaged)
            {
                $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $character->Id, $owner->Id, $this->Id);
                $engageEvent->batchId = $batchId;
                $theah->queueEvent($engageEvent);
            }
            else
            {
                $moveEvent = EventFactory::createCardMovingEvent($owner->ControllerId, $character->Id, $character->Location, Game::LOCATION_PLAYER_HOME, false, $owner->Id, $this->Id);
                $moveEvent->batchId = $batchId;
                $theah->queueEvent($moveEvent);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $theah->queueEvent($actionResolvedEvent);
        }
    }

    function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01026)
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
            return [false, $game->translate("Target character is the same as the performer")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01026)
        {
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

            $owner = $this->getOwningCard($game->theah);

            $performer->unEquipAllAttachments($game->theah);
            $destroyedEvent = EventFactory::createCharacterDestroyedEvent($performer->ControllerId, $performer->Id, $owner->getInjectCode());
            $game->theah->queueEvent($destroyedEvent);

            // WHY: Destroy performer above is the cost. Manipulate + ActionResolved emit from
            // handleEvent only when EventCharacterTargeted is not canceled.
            $batchId = $game->getNextEventBatchId();
            $targetedEvent = EventFactory::createCharacterTargetedEvent(
                $owner->ControllerId,
                $character->Id,
                $owner->Id,
                $this->Id
            );
            $targetedEvent->batchId = $batchId;
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState();
        }
    }
}
