<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01019 extends CharacterAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Destroy Buratino, Wound Character");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }
        
        $owner = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($owner))
        {
            return false;
        }

        $characters = $theah->getCharactersAtLocation($owner->Location);
        $characters = array_filter($characters, fn($character) => $character->Id != $owner->Id);
        return count($characters) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01019", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }

        // WHY: Destroy + wound + resolve are all resolution effects, so all are gated on the
        // targeting surviving. A canceled action must not destroy Buratino for nothing.
        // unEquipAllAttachments mutates state, so it lives on the surviving path only.
        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $theah = $event->theah;
            $owner = $this->getOwningCharacter($theah);
            $target = $theah->getCharacterById($event->targetId);
            if ($owner === null || $target === null)
            {
                return;
            }

            $batchId = $event->batchId ?? $theah->game->getNextEventBatchId();

            $owner->unEquipAllAttachments($theah);
            $destroyEvent = EventFactory::createCharacterDestroyedEvent($owner->ControllerId, $owner->Id, $owner->getInjectCode());
            $destroyEvent->batchId = $batchId;
            $theah->queueEvent($destroyEvent);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($target->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $woundEvent->batchId = $batchId;
            $theah->queueEvent($woundEvent);

            $theah->queueEvent(EventFactory::createActionResolvedEvent($owner->ControllerId));
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01019)
        {
            $owner = $this->getOwningCharacter($game->theah);

            $args["performerId"] = $owner->Id;

            $characters = $game->theah->getCharactersAtLocation($owner->Location);
            $characters = array_values(array_filter($characters, fn($character) => $character->Id != $owner->Id));

            $args["ids"] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;

    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $owner = $this->getOwningCharacter($game->theah);
        if ($character->Location != $owner->Location)
        {
            return [false, $game->translate("Target character is not at the same location as the performer")];
        }
        if ($character->Id == $owner->Id)
        {
            return [false, $game->translate("Target character is the same as the performer")];
        }
        
        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01019)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new UserException(sprintf($game->translate("Invalid target character id: %d"), $id));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            // WHY: Quick Reflexes cancels the wound before it is logged. Name the
            // target here so the log shows who was chosen.
            $game->notify->all("message", clienttranslate('${owner_inject_code} targets ${target_inject_code} to be wounded.'), [
                "owner_inject_code" => $owner->getInjectCode(),
                "target_inject_code" => $target->getInjectCode(),
            ]);

            // WHY: Fire targeting alone here. Destroy Buratino + wound + ActionResolved emit from
            // handleEvent only when EventCharacterTargeted survives (Cloud Bottle / UL / Maryam).
            $targetedEvent = EventFactory::createCharacterTargetedEvent(
                $owner->ControllerId,
                $target->Id,
                $owner->Id,
                $this->Id
            );
            $targetedEvent->batchId = $game->getNextEventBatchId();
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState("characterChosen");
        }
    }
    
}