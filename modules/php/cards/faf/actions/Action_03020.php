<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

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

class Action_03020 extends RiskAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Opposing Character Home");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $leader = $theah->getLeaderByPlayerId($playerId);
        if ($leader === null) return false;
        if (! $theah->cardInCity($leader)) return false;

        return count($this->getValidTargets($theah, $leader)) > 0;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $owner = $this->getOwningCard($game->theah);
        $leader = $game->theah->getLeaderByPlayerId($owner->ControllerId);
        if ($leader === null)
        {
            return [false, $game->translate("You no longer have a Leader in play.")];
        }

        if ($character->ControllerId == $leader->ControllerId || $character->ControllerId == 0)
        {
            return [false, $game->translate("Target must be controlled by an opponent.")];
        }

        if ($character->Location != $leader->Location)
        {
            return [false, $game->translate("Target must be at your Leader's location.")];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03020", $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            $character = $event->theah->getCharacterById($event->targetId);
            if ($owner === null || $character === null)
            {
                return;
            }

            $moveEvent = EventFactory::createCardMovingEvent($owner->ControllerId, $character->Id, $character->Location, Game::LOCATION_PLAYER_HOME, $engage = false, $owner->Id, $this->Id);
            $moveEvent->batchId = $event->batchId ?? $event->theah->game->getNextEventBatchId();
            $event->theah->eventCheck($moveEvent);
            $event->theah->queueEvent($moveEvent);

            // ActionResolved stays unbatched (01160 convention) so a batch strip cannot eat it.
            $event->theah->queueEvent(EventFactory::createActionResolvedEvent($owner->ControllerId));
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03020)
        {
            $owner = $this->getOwningCard($game->theah);
            $leader = $game->theah->getLeaderByPlayerId($owner->ControllerId);

            $args['performerId'] = $leader->Id;
            $args['ids'] = array_map(fn(Character $c) => $c->Id, $this->getValidTargets($game->theah, $leader));
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03020)
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
            $leader = $game->theah->getLeaderByPlayerId($owner->ControllerId);

            // WHY: Fire targeting alone here. The move + ActionResolved emit from handleEvent
            // only when EventCharacterTargeted survives (Cloud Bottle / UL cancel it).
            $targetedEvent = EventFactory::createCharacterTargetedEvent($leader->ControllerId, $character->Id, $owner->Id, $this->Id);
            $targetedEvent->batchId = $game->getNextEventBatchId();
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState("targetChosen");
        }
    }

    private function getValidTargets(Theah $theah, Character $leader): array
    {
        $opposing = $theah->getOpposingCharactersAtLocation($leader->Location, $leader->ControllerId);
        return array_values($opposing);
    }
}
