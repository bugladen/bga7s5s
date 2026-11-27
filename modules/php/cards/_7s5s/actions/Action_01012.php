<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01012 extends CharacterAction implements ISorcererAbility, IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Wound Sibella, Wound Opposing Character");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
            return false;

        $sibella = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($sibella))
            return false;

        // WHY queryCard: Patricia/Uwe trait fiction only fires when the ability identifies itself.
        if (! $sibella->hasTrait("Sorcerer", $sibella))
            return false;

        $characters = $theah->getOpposingCharactersAtLocation($sibella->Location, $sibella->ControllerId);
        return count($characters) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $sibella = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($sibella->ControllerId, $sibella->Id, "01012", $this->Id);
            $event->theah->queueEvent($transition);
        }

        // WHY: Sibella's self-wound, the Sorcerer start/played pair, the target wound and
        // ActionResolved are all resolution effects, so all wait for EventCharacterTargeted to
        // survive. Cloud Bottle / UL / Maryam cancel that hook; queuing beside the pick meant
        // "when targeted" reactions never saw this action (journal 2026-11-27-02).
        // Self-wound is gated too: a canceled action must not leave Sibella paying a pure cost.
        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $theah = $event->theah;
            $performer = $this->getOwningCharacter($theah);
            $target = $theah->getCharacterById($event->targetId);
            if ($performer === null || $target === null)
            {
                return;
            }

            $batchId = $event->batchId ?? $theah->game->getNextEventBatchId();

            $selfWound = EventFactory::createCharacterBeingWoundedEvent($performer->Id, $performer->Id, 1, $performer->getInjectCode());
            $selfWound->batchId = $batchId;
            $theah->queueEvent($selfWound);

            $theah->queueEvent(EventFactory::createSorcererAbilityStartEvent($performer->ControllerId, $performer->Id, $this->Id, $performer->Id, $target->Id, $target->Location));

            $targetWound = EventFactory::createCharacterBeingWoundedEvent($target->Id, $performer->Id, 1, $performer->getInjectCode(), $this->Id);
            $targetWound->batchId = $batchId;
            $theah->queueEvent($targetWound);

            $theah->queueEvent(EventFactory::createSorcererAbilityPlayedEvent($performer->ControllerId, $performer->Id, $this->Id, $performer->Id, $target->Id, $target->Location));

            $theah->queueEvent(EventFactory::createActionResolvedEvent($performer->ControllerId));
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01012)
        {
            $performer = $this->getOwningCharacter($game->theah);
            $args["performerId"] = $performer->Id;

            $characters = $game->theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
            $args["characterIds"] = array_map(fn($c) => $c->Id, $characters);
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performer = $this->getOwningCharacter($game->theah);

        if ($character->ControllerId == $performer->ControllerId)
        {
            return [false, $game->translate("Target character must be an opposing character")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Target character is not at the same location as the performer")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01012)
        {
           $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $owner = $this->getOwningCharacter($game->theah);
            $game->notify->all("message", clienttranslate('${owner_inject_code}: ${target_inject_code} is the target.'), [
                "owner_inject_code" => $owner->getInjectCode(),
                "target_inject_code" => $target->getInjectCode(),
            ]);

            // WHY: Fire targeting alone here. Self-wound, Sorcerer events, target wound and
            // ActionResolved emit from handleEvent only when EventCharacterTargeted survives.
            $targetedEvent = EventFactory::createCharacterTargetedEvent(
                $owner->ControllerId,
                $target->Id,
                $owner->Id,
                $this->Id
            );
            $targetedEvent->batchId = $game->getNextEventBatchId();
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState("opposingCharacterChosen");
        }
    }

}