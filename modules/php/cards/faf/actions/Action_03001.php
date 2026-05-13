<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03001 extends CharacterAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Wound from Strega to Opposing Non-Leader");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $cesca = $this->getOwningCharacter($theah);

        if (! $theah->cardInCity($cesca))
        {
            return false;
        }

        if (count($this->getStregaSources($theah, $cesca)) == 0)
        {
            return false;
        }

        if (count($this->getOpposingNonLeaderTargets($theah, $cesca)) == 0)
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $cesca = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $cesca->Id, "03001", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $cesca = $this->getOwningCharacter($game->theah);

        if ($character->ControllerId == $cesca->ControllerId)
        {
            return [false, $game->translate("Target must be controlled by an opponent.")];
        }

        if ($character->Location != $cesca->Location)
        {
            return [false, $game->translate("Target must be at Cesca's location.")];
        }

        if ($character->hasTrait("Leader"))
        {
            return [false, $game->translate("Target cannot be a Leader.")];
        }

        return [true, ""];
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03001)
        {
            $cesca = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $cesca->Id;

            $stregas = $this->getStregaSources($game->theah, $cesca);
            $args['ids'] = array_map(fn($character) => $character->Id, $stregas);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03001_2)
        {
            $cesca = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $cesca->Id;

            $sourceId = $game->globals->get(Game::CHOSEN_CARD);
            $args['sourceId'] = $sourceId;

            $targets = $this->getOpposingNonLeaderTargets($game->theah, $cesca);
            $args['ids'] = array_map(fn($character) => $character->Id, $targets);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03001)
        {
            $strega = $game->theah->getCharacterById($id);
            if ($strega == null)
            {
                throw new UserException($game->translate("Invalid character."));
            }

            $cesca = $this->getOwningCharacter($game->theah);

            if ($strega->ControllerId != $cesca->ControllerId)
            {
                throw new UserException($game->translate("Strega must be controlled by you."));
            }

            if ($strega->Location != $cesca->Location)
            {
                throw new UserException($game->translate("Strega must be at Cesca's location."));
            }

            if (! $strega->hasTrait("Strega"))
            {
                throw new UserException($game->translate("Character must have the Strega trait."));
            }

            if ($strega->Wounds <= 0)
            {
                throw new UserException($game->translate("Strega has no wound to move."));
            }

            $game->globals->set(Game::CHOSEN_CARD, $strega->Id);

            $game->gamestate->nextState("stregaChosen");
            return;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03001_2)
        {
            $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new UserException($game->translate("Invalid character."));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $cesca = $this->getOwningCharacter($game->theah);
            $sourceId = $game->globals->get(Game::CHOSEN_CARD);
            $strega = $game->theah->getCharacterById($sourceId);

            if ($strega == null || $strega->Wounds <= 0 || $strega->Location != $cesca->Location)
            {
                throw new UserException($game->translate("Strega is no longer valid as a source."));
            }

            $healEvent = EventFactory::createCharacterBeingHealedEvent($strega->Id, $cesca->Id, 1, $cesca->getInjectCode(), $this->Id);
            $game->theah->queueEvent($healEvent);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($target->Id, $cesca->Id, 1, $cesca->getInjectCode(), $this->Id);
            $game->theah->queueEvent($woundEvent);

            $game->notify->all("message", clienttranslate('${owner_inject_code}: ${player_name} moves a wound from ${strega_inject_code} to ${target_inject_code}.'), [
                "owner_inject_code" => $cesca->getInjectCode(),
                "player_name" => $game->getPlayerNameById($cesca->ControllerId),
                "strega_inject_code" => $strega->getInjectCode(),
                "target_inject_code" => $target->getInjectCode(),
            ]);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($cesca->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("targetChosen");
            return;
        }
    }

    private function getStregaSources(Theah $theah, Character $cesca): array
    {
        $characters = $theah->getCharactersAtLocationByPlayerId($cesca->Location, $cesca->ControllerId);
        return array_values(array_filter($characters, fn($character) => $character->hasTrait("Strega") && $character->Wounds > 0));
    }

    private function getOpposingNonLeaderTargets(Theah $theah, Character $cesca): array
    {
        $characters = $theah->getCharactersAtLocation($cesca->Location);
        return array_values(array_filter($characters, fn($character) => $character->isNotControlledByPlayer($cesca->ControllerId) && ! $character->hasTrait("Leader")));
    }
}
