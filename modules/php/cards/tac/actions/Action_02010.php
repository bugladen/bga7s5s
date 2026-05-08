<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02010 extends RiskCityAction implements ISorcererAbility, IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Wounds Between Characters");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $locations = $theah->getCityLocations();
        foreach ($locations as $location)
        {
            $characters = $theah->getCharactersAtLocationByPlayerId($location->Name, $playerId);
            $stregas = array_values(array_filter($characters, fn($character) => $character->hasTrait("Sorcerer") && $character->hasTrait("Strega")));
            $woundedCharacters = array_values(array_filter($characters, fn($character) => $character->Wounds > 0));
            if (count($stregas) > 0 && count($characters) >= 2 && count($woundedCharacters) > 0)
            {
                return true;
            }   
        }

        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        $stregas = array_values(array_filter($characters, fn($character) => $character->hasTrait("Sorcerer") && $character->hasTrait("Strega")));
        $performers = [];
        foreach ($stregas as $strega)
        {
            $charactersInLocation = $theah->getCharactersAtLocationByPlayerId($strega->Location, $playerId);
            $woundedCharacters = array_values(array_filter($charactersInLocation, fn($character) => $character->Wounds > 0));
            if (count($charactersInLocation) >= 2 && count($woundedCharacters) > 0)
            {
                $performers[$strega->Id] = $strega;
            }
        }

        return array_values($performers);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "02010", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }   
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02010)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;

            $characters = $game->theah->getCharactersAtLocationByPlayerId($performer->Location, $performer->ControllerId);
            $characters = array_values(array_filter($characters, fn($character) => $character->Wounds > 0));
            $args["ids"] = array_map(fn($character) => $character->Id, $characters);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02010_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;

            $fromId = $game->globals->get(Game::CHOSEN_CARD);
            $from = $game->theah->getCharacterById($fromId);
            $args["fromId"] = $fromId;

            $characters = $game->theah->getCharactersAtLocationByPlayerId($performer->Location, $performer->ControllerId);
            $characters = array_values(array_filter($characters, fn($character) => $character->Id != $fromId));
            $args["ids"] = array_map(fn($character) => $character->Id, $characters);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02010_3)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performer->Id;

            $fromId = $game->globals->get(Game::CHOSEN_CARD);
            $from = $game->theah->getCharacterById($fromId);
            $args["fromId"] = $fromId;
            $args["fromName"] = $from->Name;

            $targetId = $game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);
            $args["targetId"] = $targetId;
            $args["targetName"] = $target->Name;

            $args["wounds"] = $from->Wounds;
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->ControllerId != $performer->ControllerId)
        {
            return [false, $game->translate("You cannot move wounds from a character that is not yours")];
        }

        if ($character->Wounds == 0)
        {
            return [false, $game->translate("Character has no wounds to move")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Character is not at the same location as the performer")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02010)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new UserException(sprintf($game->translate("Invalid character id: %d"), $id));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $game->globals->set(Game::CHOSEN_CARD, $character->Id);
            $game->gamestate->nextState();
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02010_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $fromId = $game->globals->get(Game::CHOSEN_CARD);
            $from = $game->theah->getCharacterById($fromId);

            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new UserException(sprintf($game->translate("Invalid character id: %d"), $id));
            }

            if ($character->ControllerId != $performer->ControllerId)
            {
                throw new UserException($game->translate("You cannot move wounds to a character that is not yours"));
            }

            if ($character->Location != $performer->Location)
            {
                throw new UserException($game->translate("Character is not at the same location as the performer"));
            }

            if ($character->Id == $fromId)
            {
                throw new UserException($game->translate("You cannot move wounds to the same character"));
            }

            $game->globals->set(Game::CHOSEN_TARGET, $character->Id);
            $game->gamestate->nextState("characterChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02010_3)
        {
            if ($id != 1 && $id != 2)
            {
                throw new UserException(sprintf($game->translate("Invalid wounds count: %d"), $id));
            }

            $fromId = $game->globals->get(Game::CHOSEN_CARD);
            $from = $game->theah->getCharacterById($fromId);

            if ($from->Wounds < $id)
            {
                throw new UserException(sprintf($game->translate("Character has not enough wounds to move: %d"), $id));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);

            $targetId = $game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);

            $owner = $this->getOwningCard($game->theah);

            $startEvent = EventFactory::createSorcererAbilityStartEvent($owner->ControllerId, $owner->Id, $this->Id, $performerId, $from->Id, $from->Location);
            $game->theah->queueEvent($startEvent);

            $healEvent = EventFactory::createCharacterBeingHealedEvent($from->Id, $owner->Id, $id, $owner->getInjectCode(), $this->Id);
            $game->theah->queueEvent($healEvent);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($target->Id, $owner->Id, $id, $owner->getInjectCode(), $this->Id);
            $game->theah->queueEvent($woundEvent);

            $sorcererAbilityEvent = EventFactory::createSorcererAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $performerId, $from->Id, $from->Location);
            $game->theah->queueEvent($sorcererAbilityEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("woundsChosen");
        }
    }
}