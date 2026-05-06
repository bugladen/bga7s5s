<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03cd01 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Move Penya and Companion to Adjacent Location');
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);

        if (!$theah->cardInCity($owner))
        {
            return false;
        }

        if ($owner->Engaged)
        {
            return false;
        }

        // Must have another friendly character at the same location
        $characters = $theah->getCharactersAtLocationByPlayerId($owner->Location, $playerId);
        $companions = array_filter($characters, fn($c) => $c->Id != $owner->Id);

        return count($companions) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "03cd01", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03CD01)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $owner->Id;

            $characters = $game->theah->getCharactersAtLocationByPlayerId($owner->Location, $owner->ControllerId);
            $companions = array_values(array_filter($characters, fn($c) => $c->Id != $owner->Id));
            $args['ids'] = array_map(fn($c) => $c->Id, $companions);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03CD01_2)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $owner->Id;

            $targetId = $game->globals->get(Game::CHOSEN_TARGET);
            $args['targetId'] = $targetId;

            $args['locationIds'] = $game->theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03CD01)
        {
            $companion = $game->theah->getCharacterById($id);
            if ($companion == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            $owner = $this->getOwningCharacter($game->theah);

            if ($companion->Id == $owner->Id)
            {
                throw new UserException($game->translate("You must choose a different character"));
            }

            if ($companion->ControllerId != $owner->ControllerId)
            {
                throw new UserException($game->translate("Character must be controlled by you"));
            }

            if ($companion->Location != $owner->Location)
            {
                throw new UserException($game->translate("Character is not at the same location as Penya"));
            }

            $game->globals->set(Game::CHOSEN_TARGET, $companion->Id);

            $game->gamestate->nextState("companionChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03CD01_2)
        {
            $location = $ids[0];
            $owner = $this->getOwningCharacter($game->theah);
            $targetId = $game->globals->get(Game::CHOSEN_TARGET);
            $companion = $game->theah->getCharacterById($targetId);

            $locations = $game->theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
            if (!in_array($location, $locations))
            {
                throw new UserException(sprintf($game->translate("Location is not adjacent to %s."), $owner->Location));
            }

            // Commit: engage Penya (cost)
            $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $owner->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($engageEvent);

            // Move companion to location
            $moveEvent = EventFactory::createCardMovingEvent($owner->ControllerId, $companion->Id, $companion->Location, $location, $engage = false, $owner->Id, $this->Id);
            $game->theah->queueEvent($moveEvent);

            // Move Penya to location
            $moveEvent = EventFactory::createCardMovingEvent($owner->ControllerId, $owner->Id, $owner->Location, $location, $engage = false, $owner->Id, $this->Id);
            $game->theah->queueEvent($moveEvent);

            $game->notify->all("message", clienttranslate('${player_name} moves ${performer_inject_code} and ${companion_inject_code} to ${location_name}.'), [
                "i18n" => ["location_name"],
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "performer_inject_code" => $owner->getInjectCode(),
                "companion_inject_code" => $companion->getInjectCode(),
                "location_name" => $location,
            ]);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }
}
