<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
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

class Action_01171 extends RiskAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Manipulate Opposing Mercenary");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $performers = $theah->getCharactersInCityWithOpposingMercenaries($playerId);
        return count($performers) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $theah->getCharactersInCityWithOpposingMercenaries($playerId);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01171", $this->Id);
            $event->theah->queueEvent($transition);
        }

        // WHY: Manipulate (engage, or send Home if already Engaged) only when
        // EventCharacterTargeted survives. Engaged state is read at resolution.
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

    public function getActionFromHandDiscount(Theah $theah, ?Character $performer, CardAction $action, Array &$explanations): int
    {
        $discount = parent::getActionFromHandDiscount($theah, $performer, $action, $explanations);

        if ($action->Id == $this->Id)
        {
            $owner = $this->getOwningCard($theah);
            // WHY queryCard: Patricia/Uwe trait fiction only fires when the ability identifies itself.
            if ($performer && ($performer->hasTrait('Villain', $owner) || $performer->hasTrait('Scoundrel', $owner)))
            {
                $discount += 1;
                $explanations[] = sprintf($theah->game->translate("%s: -1 because Performer is a Villain or Scoundrel."), $owner->getInjectCode());
            }
        }

        return $discount;
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01171)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;

            $characters = $game->theah->getCharactersAtLocation($performer->Location);
            $characters = array_values(array_filter($characters, fn($character) => $character->isNotControlledByPlayer($performer->ControllerId) && $character->hasTrait("Mercenary")));
            $args["ids"] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($performer->Location != $character->Location)
        {
            return [false, $game->translate("Character is not at the same location as the performer.")];
        }

        // WHY: Args already filter opposing; re-check so a client bypass cannot engage/Home your Merc.
        if (! $character->isNotControlledByPlayer($performer->ControllerId))
        {
            return [false, $game->translate("Character is not opposing your performer.")];
        }

        if (! $character->hasTrait('Mercenary'))
        {
            return [false, $game->translate("Character is not a Mercenary.")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01171)
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

            // WHY: Fire targeting alone here. Engage / send Home + ActionResolved emit from
            // handleEvent only when EventCharacterTargeted survives (Cloud Bottle / UL / Maryam
            // cancel that hook).
            $targetedEvent = EventFactory::createCharacterTargetedEvent(
                $owner->ControllerId,
                $character->Id,
                $owner->Id,
                $this->Id
            );
            $targetedEvent->batchId = $game->getNextEventBatchId();
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState();
        }
    }
}