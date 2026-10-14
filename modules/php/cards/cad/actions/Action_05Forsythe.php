<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_05Forsythe extends AttachmentAction
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Engage; Move an adjacent Renown to this location";
    }

    /**
     * @return list<string> adjacent city location names that have Renown > 0
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

        $attachment = $this->getOwningAttachment($theah);
        if ($attachment === null || $attachment->Engaged)
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if ($owner === null || ! $theah->cardInCity($owner))
        {
            return false;
        }

        // WHY: Covert Action — Covert abilities only at uncontrolled City locations (Valeri / Overcoat).
        $cityLocation = $theah->getCityLocation($owner->Location);
        if ($cityLocation === null || $cityLocation->isControlled())
        {
            return false;
        }

        return count($this->getAdjacentLocationsWithRenown($theah, $owner)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $attachment = $this->getOwningAttachment($event->theah);
            $transition = EventFactory::createTransitionEvent(
                $attachment->ControllerId,
                $attachment->Id,
                "05Forsythe",
                $this->Id
            );
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05FORSYTHE)
        {
            $owner = $this->getOwningCharacter($game->theah);

            $args["performerId"] = $owner->Id;
            $args["locationIds"] = $this->getAdjacentLocationsWithRenown($game->theah, $owner);
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05FORSYTHE)
        {
            $fromLocationName = $ids[0] ?? '';
            $owner = $this->getOwningCharacter($game->theah);
            $attachment = $this->getOwningAttachment($game->theah);

            if ($owner === null || $attachment === null)
            {
                throw new UserException("Equipped character not found.");
            }

            // WHY: Keep Covert honest if control flipped between announce and resolve.
            $cityLocation = $game->theah->getCityLocation($owner->Location);
            if ($cityLocation === null || $cityLocation->isControlled())
            {
                throw new UserException("Covert Actions can only be used at uncontrolled City locations.");
            }

            $eligible = $this->getAdjacentLocationsWithRenown($game->theah, $owner);
            if (! in_array($fromLocationName, $eligible, true))
            {
                throw new UserException("Must move Renown from an adjacent location that has Renown.");
            }

            // WHY: printed cost is engage this attachment, not the performer — pay on resolve with the move.
            $engageEvent = EventFactory::createCardEngagedEvent(
                $attachment->ControllerId,
                $attachment->Id,
                $attachment->Id,
                $this->Id
            );
            $game->theah->queueEvent($engageEvent);

            $batchId = $game->getNextEventBatchId();

            $movingEvent = EventFactory::createRenownMovingBetweenLocationsEvent(
                $attachment->ControllerId,
                $fromLocationName,
                $owner->Location,
                1,
                $attachment->getInjectCode()
            );
            $movingEvent->batchId = $batchId;
            $game->theah->eventCheck($movingEvent);
            $game->theah->queueEvent($movingEvent);

            $removeEvent = EventFactory::createRenownRemovedFromLocationEvent(
                $attachment->ControllerId,
                $fromLocationName,
                1,
                $attachment->getInjectCode()
            );
            $removeEvent->batchId = $batchId;
            $game->theah->eventCheck($removeEvent);
            $game->theah->queueEvent($removeEvent);

            $addEvent = EventFactory::createRenownAddedToLocationEvent(
                $attachment->ControllerId,
                $owner->Location,
                1,
                $attachment->getInjectCode(),
                $isMove = true
            );
            $addEvent->batchId = $batchId;
            $game->theah->eventCheck($addEvent);
            $game->theah->queueEvent($addEvent);

            $game->notify->all("message", '${card_inject_code}: ${player_name} moved 1 Renown from ${from_location} to ${to_location}.', [
                'i18n' => ['from_location', 'to_location'],
                'card_inject_code' => $attachment->getInjectCode(),
                'player_name' => $game->getPlayerNameById($attachment->ControllerId),
                'from_location' => $fromLocationName,
                'to_location' => $owner->Location,
            ]);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($attachment->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }
}
