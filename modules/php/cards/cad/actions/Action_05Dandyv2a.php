<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_05Dandyv2a extends CharacterAction
{
    use DandyWealthPayment;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Pay 1 Wealth; Move a Renown from this location to an adjacent City location";
    }

    /**
     * Adjacent City locations (never Home). Printed destinations for the Renown move.
     *
     * @return string[]
     */
    private function getDestinations(Theah $theah, string $currentLocation): array
    {
        return $theah->getAdjacentCityLocations($currentLocation, $includeHome = false);
    }

    private function locationHasRenown(Theah $theah, string $location): bool
    {
        // WHY: getCityLocation throws for Home — check city first.
        if (! $theah->locationInCity($location))
        {
            return false;
        }

        return $theah->getCityLocation($location)->Renown > 0;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        // WHY: Printed as Action (not City Action), but Renown only exists on City
        // locations — Home has none.
        if (! $this->locationHasRenown($theah, $owner->Location))
        {
            return false;
        }

        if (! $this->playerCanPayOneWealth($playerId, $theah))
        {
            return false;
        }

        return count($this->getDestinations($theah, $owner->Location)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05Dandyv2a", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05DANDYV2A
            || $state == States::HIGH_DRAMA_PLAYER_TURN_05DANDYV2A_2)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $owner->Id;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05DANDYV2A_2)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args['locationIds'] = $this->getDestinations($game->theah, $owner->Location);
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        $owner = $this->getOwningCharacter($game->theah);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05DANDYV2A)
        {
            // WHY: Refuse before the discard so a stale client cannot pay when
            // Renown is already gone (or Claude left the city) between announce
            // and confirm.
            if (! $this->locationHasRenown($game->theah, $owner->Location))
            {
                throw new UserException("This location has no Renown to move.");
            }
            if (count($this->getDestinations($game->theah, $owner->Location)) === 0)
            {
                throw new UserException("There is no adjacent City location.");
            }

            $this->queueOneWealthPayment($game, $owner, $ids);

            // WHY: Return to the event queue so the discard resolves before the
            // location pick. The hand must already be missing the paid card.
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05Dandyv2a_2", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState();
            return;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05DANDYV2A_2)
        {
            $locationName = $ids[0] ?? '';
            $eligible = $this->getDestinations($game->theah, $owner->Location);
            if (! in_array($locationName, $eligible, true))
            {
                throw new UserException("Choose an adjacent City location.");
            }

            if (! $this->locationHasRenown($game->theah, $owner->Location))
            {
                // WHY: Wealth already spent — finish cleanly rather than soft-lock.
                $game->notify->all("message", '${claude_inject_code}: ${location} has no Renown left to move.', [
                    'i18n' => ['location'],
                    'claude_inject_code' => $owner->getInjectCode(),
                    'location' => $owner->Location,
                ]);

                $resolved = EventFactory::createActionResolvedEvent($owner->ControllerId);
                $game->theah->queueEvent($resolved);
                $game->gamestate->nextState();
                return;
            }

            $batchId = $game->getNextEventBatchId();
            $reason = $owner->getInjectCode();

            $movingEvent = EventFactory::createRenownMovingBetweenLocationsEvent(
                $owner->ControllerId,
                $owner->Location,
                $locationName,
                1,
                $reason
            );
            $movingEvent->batchId = $batchId;
            $game->theah->eventCheck($movingEvent);
            $game->theah->queueEvent($movingEvent);

            $removeEvent = EventFactory::createRenownRemovedFromLocationEvent(
                $owner->ControllerId,
                $owner->Location,
                1,
                $reason
            );
            $removeEvent->batchId = $batchId;
            $game->theah->eventCheck($removeEvent);
            $game->theah->queueEvent($removeEvent);

            $addEvent = EventFactory::createRenownAddedToLocationEvent(
                $owner->ControllerId,
                $locationName,
                1,
                $reason,
                $isMove = true
            );
            $addEvent->batchId = $batchId;
            $game->theah->eventCheck($addEvent);
            $game->theah->queueEvent($addEvent);

            $game->notify->all("message", '${card_inject_code}: ${player_name} moved 1 Renown from ${from_location} to ${to_location}.', [
                'i18n' => ['from_location', 'to_location'],
                'card_inject_code' => $owner->getInjectCode(),
                'player_name' => $game->getPlayerNameById($owner->ControllerId),
                'from_location' => $owner->Location,
                'to_location' => $locationName,
            ]);

            $resolved = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($resolved);

            $game->gamestate->nextState();
        }
    }
}
