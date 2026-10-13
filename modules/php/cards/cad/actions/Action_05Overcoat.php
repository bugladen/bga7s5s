<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_05Overcoat extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Engage; Pressure with Resolve; claim or move adjacent Renown";
    }

    /**
     * @return string[] adjacent city location names that have Renown > 0
     */
    private function getAdjacentLocationsWithRenown(Theah $theah, Character $owner): array
    {
        $adjacent = $theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
        $result = [];
        foreach ($adjacent as $locationName)
        {
            $location = $theah->getCityLocation($locationName);
            if ($location !== null && $location->Renown > 0)
            {
                $result[] = $locationName;
            }
        }
        return $result;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);

        if (! $theah->cardInCity($owner))
        {
            return false;
        }

        // WHY: Covert Action — Covert abilities only at uncontrolled City locations (Valeri).
        $cityLocation = $theah->getCityLocation($owner->Location);
        if ($cityLocation === null || $cityLocation->isControlled())
        {
            return false;
        }

        if ($owner->Engaged)
        {
            return false;
        }

        if (! $owner->canPressure(Game::STAT_RESOLVE))
        {
            return false;
        }

        // WHY: Gate on at least one success payoff — claim or adjacent Renown (Soline shape).
        $canClaim = $theah->canLocationBeClaimedBy($playerId, $owner->Location);
        if (! $canClaim && count($this->getAdjacentLocationsWithRenown($theah, $owner)) === 0)
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId === $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCharacter($event->theah);

            $game->globals->set(Game::PRESSURING_PLAYER, $owner->ControllerId);
            $game->globals->set(Game::CHOSEN_PERFORMER, $owner->Id);
            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
            $game->globals->set(Game::PRESSURE_STAT, Game::STAT_RESOLVE);

            $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $owner->Id, $owner->Id, $this->Id);
            $event->theah->queueEvent($engageEvent);

            $pressureStats = $event->theah->getPressureStats($owner, $owner->Location, Game::STAT_RESOLVE);
            $pressureOccuringEvent = EventFactory::createPressureOccuringEvent(
                $owner->ControllerId,
                $owner->Id,
                $owner->Location,
                $pressureStats
            );
            $event->theah->queueEvent($pressureOccuringEvent);

            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "pressureLocation", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }

        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);

            if ($event->success)
            {
                $canClaim = $event->theah->canLocationBeClaimedBy($owner->ControllerId, $owner->Location);
                $renownLocations = $this->getAdjacentLocationsWithRenown($event->theah, $owner);

                if ($canClaim || count($renownLocations) > 0)
                {
                    $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05Overcoat", $this->Id);
                    $event->theah->queueEvent($transitionEvent);
                    return;
                }

                $event->theah->game->notify->all("message", '${location} cannot be claimed and there is no adjacent Renown to move.', [
                    'i18n' => ['location'],
                    'location' => $owner->Location,
                ]);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05OVERCOAT)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $owner->Id;
            $args['canClaim'] = $game->theah->canLocationBeClaimedBy($owner->ControllerId, $owner->Location);
            $args['locationIds'] = $this->getAdjacentLocationsWithRenown($game->theah, $owner);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05OVERCOAT)
        {
            $owner = $this->getOwningCharacter($game->theah);

            // id 0 = Claim the location
            if ($id == 0)
            {
                if (! $game->theah->canLocationBeClaimedBy($owner->ControllerId, $owner->Location))
                {
                    throw new UserException("Location cannot be claimed.");
                }

                $claimEvent = EventFactory::createLocationClaimedEvent($owner->ControllerId, $owner->Id, $owner->Location);
                $game->theah->queueEvent($claimEvent);

                $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
                $game->theah->queueEvent($actionResolvedEvent);

                $game->gamestate->nextState("choiceMade");
                return;
            }

            throw new UserException("Invalid choice.");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05OVERCOAT)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $fromLocationName = $ids[0] ?? '';

            $eligible = $this->getAdjacentLocationsWithRenown($game->theah, $owner);
            if (! in_array($fromLocationName, $eligible, true))
            {
                throw new UserException("Must move Renown from an adjacent location that has Renown.");
            }

            $batchId = $game->getNextEventBatchId();
            $movingEvent = EventFactory::createRenownMovingBetweenLocationsEvent(
                $owner->ControllerId,
                $fromLocationName,
                $owner->Location,
                1,
                $owner->getInjectCode()
            );
            $movingEvent->batchId = $batchId;
            $game->theah->eventCheck($movingEvent);
            $game->theah->queueEvent($movingEvent);

            $removedEvent = EventFactory::createRenownRemovedFromLocationEvent(
                $owner->ControllerId,
                $fromLocationName,
                1,
                $owner->getInjectCode()
            );
            $removedEvent->batchId = $batchId;
            $game->theah->eventCheck($removedEvent);
            $game->theah->queueEvent($removedEvent);

            $addedEvent = EventFactory::createRenownAddedToLocationEvent(
                $owner->ControllerId,
                $owner->Location,
                1,
                $owner->getInjectCode(),
                $isMove = true
            );
            $addedEvent->batchId = $batchId;
            $game->theah->eventCheck($addedEvent);
            $game->theah->queueEvent($addedEvent);

            $game->notify->all("message", '${card_inject_code}: ${player_name} moved 1 Renown from ${from_location} to ${to_location}.', [
                'i18n' => ['from_location', 'to_location'],
                'card_inject_code' => $owner->getInjectCode(),
                'player_name' => $game->getPlayerNameById($owner->ControllerId),
                'from_location' => $fromLocationName,
                'to_location' => $owner->Location,
            ]);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("choiceMade");
        }
    }
}
