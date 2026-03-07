<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01133;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01133 extends RiskAction implements ISorcererAbility, IAbilityThatTargetsCharacters, IAbilityThatTargetsCards
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Target Character You Control to another Location");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $performers = $theah->getCharactersInPlayByPlayerId($playerId);
        $performers = array_filter($performers, fn($performer) => $performer->hasTrait("Sorcerer"));
        return count($performers) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = $theah->getCharactersInPlayByPlayerId($playerId);
        $performers = array_filter($performers, fn($performer) => $performer->hasTrait("Sorcerer"));
        return array_values($performers);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01133", $this->Id);
            $event->theah->queueEvent($transition);

        }
    }

    public function getActionFromHandDiscount(Theah $theah, ?Character $performer, CardAction $action, array &$explanations): int
    {
        $discount = parent::getActionFromHandDiscount($theah, $performer, $action, $explanations);
        $owner = $this->getOwningCard($theah);
        if ($owner instanceof _01133 && $owner->WillEngage)
        {
            $discount += $owner->WealthCost;
        }

        return $discount;
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01133)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;

            $characters = $game->theah->getCharactersAtLocation($performer->Location);
            $characters = array_filter($characters, fn($character) => $character->ControllerId == $performer->ControllerId);
            $args["ids"] = array_map(fn($character) => $character->Id, array_values($characters));
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01133_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;

            $characterId = $game->globals->get(Game::CHOSEN_TARGET);
            $character = $game->theah->getCharacterById($characterId);
            $args["characterId"] = $characterId;

            $locations = $game->theah->getCityLocations();
            $availableLocations = array_filter($locations, fn($location) => $location->Name != $performer->Location);
            $ids = array_map(fn($location) => $location->Name, array_values($availableLocations));

            if ($performer->Location != Game::LOCATION_PLAYER_HOME)
            {
                $ids[] = Game::LOCATION_PLAYER_HOME;
            }

            $args["locationIds"] = $ids;
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);
        if ($character->ControllerId != $performer->ControllerId)
        {
            return [false, $game->translate("Character is not controlled by the same player as the performer")];
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

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01133)
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

            $game->globals->set(Game::CHOSEN_TARGET, $id);
            $game->gamestate->nextState("characterChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01133_2)
        {
            $owner = $this->getOwningCard($game->theah);
            $location = $ids[0];

            $game->globals->set(Game::CHOSEN_LOCATION, $location);

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $characterId = $game->globals->get(Game::CHOSEN_TARGET);
            $character = $game->theah->getCharacterById($characterId);

            $moveEvent = EventFactory::createCardMovingEvent($owner->ControllerId, $character->Id, $character->Location, $location, $engage = false, $owner->Id, $this->Id);
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $this->resetPlayerPassCount($game);
            $game->notify->all("message", clienttranslate('${card_inject_code}: ${performer_inject_code} was the Performer.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "performer_inject_code" => $performer->getInjectCode(),
            ]);

            $game->notify->all("message", clienttranslate('${owner_inject_code}: ${target_inject_code} was chosen as the target.'), [
                "owner_inject_code" => $owner->getInjectCode(),
                "target_inject_code" => $character->getInjectCode(),
            ]);

            $sorcererAbilityStartedEvent = EventFactory::createSorcererAbilityStartEvent($owner->ControllerId, $owner->Id, $this->Id, $performerId, $character->Id, $character->Location);
            $game->theah->queueEvent($sorcererAbilityStartedEvent);

            $transition = EventFactory::createTransitionEvent($character->ControllerId, $owner->Id, "01133_3", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState("locationChosen");
        }
    }

    public function stateFromAction(Game $game, int $state, string $stateName): void
    {
        parent::stateFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01133_3)
        {
            $owner = $this->getOwningCard($game->theah);

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);

            $characterId = $game->globals->get(Game::CHOSEN_TARGET);
            $character = $game->theah->getCharacterById($characterId);

            $location = $game->globals->get(Game::CHOSEN_LOCATION);

            $moveEvent = EventFactory::createCardMovingEvent($owner->ControllerId, $character->Id, $character->Location, $location, $engage = false, $owner->Id, $this->Id);
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $this->resetPlayerPassCount($game);
            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $sorcererAbilityPlayedEvent = EventFactory::createSorcererAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $performerId, $character->Id, $character->Location);
            $game->theah->queueEvent($sorcererAbilityPlayedEvent);

            $game->gamestate->nextState();
        }
    }
}