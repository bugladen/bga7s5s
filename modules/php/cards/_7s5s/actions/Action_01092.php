<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01092 extends CharacterAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Opposing Engaged Character Home");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $makepeace = $this->getOwningCharacter($theah);

        if (! $theah->cardInCity($makepeace))
        {
            return false;
        }

        $opposingCharacters = $theah->getCharactersAtLocation($makepeace->Location);
        $opposingCharacters = array_filter($opposingCharacters, fn($character) => $character->isNotControlledByPlayer($playerId) && $character->Engaged);
        $opposingCharacters = array_filter($opposingCharacters, fn($character) => $character->ModifiedInfluence <= $makepeace->ModifiedInfluence);
        
        return count($opposingCharacters) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01092", $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $makepeace = $this->getOwningCharacter($event->theah);
            $character = $event->theah->getCharacterById($event->targetId);
            if ($makepeace === null || $character === null)
            {
                return;
            }

            $moveEvent = EventFactory::createCardMovingEvent($makepeace->ControllerId, $character->Id, $character->Location, Game::LOCATION_PLAYER_HOME, $engage = false, $makepeace->Id, $this->Id);
            $moveEvent->batchId = $event->batchId ?? $event->theah->game->getNextEventBatchId();
            $event->theah->queueEvent($moveEvent);

            // ActionResolved stays unbatched (01160 convention) so a batch strip cannot eat it.
            $event->theah->queueEvent(EventFactory::createActionResolvedEvent($makepeace->ControllerId));
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01092)
        {
            $makepeace = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $makepeace->Id;

            $opposingCharacters = $game->theah->getCharactersAtLocation($makepeace->Location);
            $opposingCharacters = array_filter($opposingCharacters, fn($character) => $character->isNotControlledByPlayer($makepeace->ControllerId) && $character->Engaged);
            $opposingCharacters = array_values(array_filter($opposingCharacters, fn($character) => $character->ModifiedInfluence <= $makepeace->ModifiedInfluence));
            $args['ids'] = array_map(fn($character) => $character->Id, $opposingCharacters);
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $makepeace = $this->getOwningCharacter($game->theah);
        if ($character->Location != $makepeace->Location)
        {
            return [false, $game->translate("Character is not at the same location as Makepeace Botwighte")];
        }
        
        if ($character->ControllerId == $makepeace->ControllerId)
        {
            return [false, $game->translate("You cannot manipulate your own character")];
        }

        if (! $character->Engaged)
        {
            return [false, $game->translate("Character is not engaged")];
        }

        if ($character->ModifiedInfluence > $makepeace->ModifiedInfluence)
        {
            return [false, $game->translate("Character has more Influence than Makepeace Botwighte")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01092)
        {
            $character = $game->theah->getCharacterById($id);

            if ($character == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            $makepeace = $this->getOwningCharacter($game->theah);

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            // WHY: Fire targeting alone here. The move + ActionResolved emit from handleEvent
            // only when EventCharacterTargeted survives (Cloud Bottle / UL cancel it).
            $targetedEvent = EventFactory::createCharacterTargetedEvent($makepeace->ControllerId, $character->Id, $makepeace->Id, $this->Id);
            $targetedEvent->batchId = $game->getNextEventBatchId();
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState("characterChosen");
        }
    }
}
