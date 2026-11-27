<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02025 extends SchemeCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->RequiresPerformerSelected = true;
        $this->Name = clienttranslate('Diplomat City Action: Move opposing character, performer, and Renown');
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->ControllerId == $performer->ControllerId)
        {
            return [false, $game->translate("You cannot choose your own character")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Character is not at the same location as the performer")];
        }

        if ($character->Influence > $performer->Influence)
        {
            return [false, $game->translate("Target character has higher Influence than the performer")];
        }

        return [true, ""];
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        // WHY queryCard: Patricia/Uwe trait fiction only fires when the ability identifies itself.
        $owner = $this->getOwningCard($theah);
        $diplomats = $theah->getCharactersInCityByPlayerId($playerId);
        $diplomats = array_filter($diplomats, fn($c) => $c->hasTrait("Diplomat", $owner));

        foreach ($diplomats as $diplomat)
        {
            $opponents = $theah->getOpposingCharactersAtLocation($diplomat->Location, $playerId);
            $opponents = array_filter($opponents, fn($c) => $c->Influence <= $diplomat->Influence);
            if (count($opponents) > 0)
            {
                $adjacentLocations = $theah->getAdjacentCityLocations($diplomat->Location, $includeHome = false);
                if (count($adjacentLocations) > 0)
                {
                    return true;
                }
            }
        }

        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        $owner = $this->getOwningCard($theah);
        $performers = array_filter($performers, fn($c) => $c->hasTrait("Diplomat", $owner));
        return array_values($performers);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "02025", $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $theah = $event->theah;
            $game = $theah->game;
            $owner = $this->getOwningCard($theah);
            $character = $theah->getCharacterById($event->targetId);
            $performer = $theah->getCharacterById((int)$game->globals->get(Game::CHOSEN_PERFORMER));
            $location = (string)$game->globals->get(Game::CHOSEN_LOCATION);
            if ($owner === null || $character === null || $performer === null || $location === '')
            {
                return;
            }

            $sourceLocation = $performer->Location;
            $batchId = $event->batchId ?? $game->getNextEventBatchId();

            $moveTarget = EventFactory::createCardMovingEvent($performer->ControllerId, $character->Id, $character->Location, $location, $engage = false, $owner->Id, $this->Id);
            $moveTarget->batchId = $batchId;
            $theah->queueEvent($moveTarget);

            $movePerformer = EventFactory::createCardMovingEvent($performer->ControllerId, $performer->Id, $performer->Location, $location, $engage = false, $owner->Id, $this->Id);
            $movePerformer->batchId = $batchId;
            $theah->queueEvent($movePerformer);

            $sourceLocationObj = $theah->getCityLocation($sourceLocation);
            if ($sourceLocationObj !== null && $sourceLocationObj->Renown > 0)
            {
                $moveRenown = EventFactory::createRenownMovingBetweenLocationsEvent($performer->ControllerId, $sourceLocation, $location, 1, $owner->getInjectCode());
                $moveRenown->batchId = $batchId;
                $theah->eventCheck($moveRenown);
                $theah->queueEvent($moveRenown);

                $removeRenown = EventFactory::createRenownRemovedFromLocationEvent($performer->ControllerId, $sourceLocation, 1, $owner->getInjectCode());
                $removeRenown->batchId = $batchId;
                $theah->eventCheck($removeRenown);
                $theah->queueEvent($removeRenown);

                $addRenown = EventFactory::createRenownAddedToLocationEvent($performer->ControllerId, $location, 1, $owner->getInjectCode(), $isMove = true);
                $addRenown->batchId = $batchId;
                $theah->eventCheck($addRenown);
                $theah->queueEvent($addRenown);
            }

            // ActionResolved stays unbatched (01160 convention) so a batch strip cannot eat it.
            $theah->queueEvent(EventFactory::createActionResolvedEvent($performer->ControllerId));
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02025)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $args['performerId'] = $performerId;

            $characters = $game->theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
            $characters = array_filter($characters, fn($c) => $c->Influence <= $performer->Influence);
            $args['ids'] = array_map(fn($c) => $c->Id, array_values($characters));
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02025_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $args['performerId'] = $performerId;

            $characterId = $game->globals->get(Game::CHOSEN_TARGET);
            $args['characterId'] = $characterId;

            $adjacentLocations = $game->theah->getAdjacentCityLocations($performer->Location, $includeHome = false);
            $args['locationIds'] = $adjacentLocations;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02025)
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

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02025_2)
        {
            $location = $ids[0];

            if ($game->theah->getCityLocation($location) == null)
            {
                throw new UserException($game->translate("Location not found"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $adjacentLocations = $game->theah->getAdjacentCityLocations($performer->Location, $includeHome = false);
            if (! in_array($location, $adjacentLocations))
            {
                throw new UserException($game->translate("Location is not adjacent to the performer"));
            }

            $characterId = $game->globals->get(Game::CHOSEN_TARGET);
            $character = $game->theah->getCharacterById($characterId);
            $owner = $this->getOwningCard($game->theah);

            // WHY: Targeting fires at the FINAL confirm (destination), not at the character pick:
            // the character pick routes straight into the `_2` destination GameState (with Back),
            // so firing earlier would leave a stale targeting event if the player backs out.
            // The destination rides CHOSEN_LOCATION so handleEvent can emit the moves + Renown
            // shift only when EventCharacterTargeted survives (Cloud Bottle / UL cancel it).
            // Nothing here is a cost, so every effect is gated. Same seat as 04028.
            $game->globals->set(Game::CHOSEN_LOCATION, $location);

            $targetedEvent = EventFactory::createCharacterTargetedEvent($performer->ControllerId, $character->Id, $owner->Id, $this->Id);
            $targetedEvent->batchId = $game->getNextEventBatchId();
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }
}
