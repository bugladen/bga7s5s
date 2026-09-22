<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04036a extends AttachmentAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move a Renown from this Location to another City Location");
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

        if (! $this->currentLocationHasRenown($theah, $owner))
        {
            return false;
        }

        return count($this->getValidDestinations($theah, $owner)) > 0;
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
                "04036a",
                $this->Id
            );
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04036a)
        {
            $owner = $this->getOwningCharacter($game->theah);

            $args["performerId"] = $owner->Id;
            $args["locationIds"] = $this->getValidDestinations($game->theah, $owner);
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04036a)
        {
            $location = $ids[0];
            $owner = $this->getOwningCharacter($game->theah);
            $attachment = $this->getOwningAttachment($game->theah);

            if ($owner === null || $attachment === null)
            {
                throw new UserException($game->translate("Equipped character not found."));
            }

            if (! $this->currentLocationHasRenown($game->theah, $owner))
            {
                throw new UserException(sprintf($game->translate("%s does not have any Renown to move."), $owner->Location));
            }

            $validDestinations = $this->getValidDestinations($game->theah, $owner);
            if (! in_array($location, $validDestinations, true))
            {
                throw new UserException($game->translate("You must choose another City location."));
            }

            // WHY: printed cost is engage this attachment, not the performer.
            $engageEvent = EventFactory::createCardEngagedEvent(
                $attachment->ControllerId,
                $attachment->Id,
                $attachment->Id,
                $this->Id
            );
            $game->theah->queueEvent($engageEvent);

            $fromLocation = $owner->Location;
            $batchId = $game->getNextEventBatchId();

            $movingEvent = EventFactory::createRenownMovingBetweenLocationsEvent(
                $attachment->ControllerId,
                $fromLocation,
                $location,
                1,
                $attachment->getInjectCode()
            );
            $movingEvent->batchId = $batchId;
            $game->theah->eventCheck($movingEvent);
            $game->theah->queueEvent($movingEvent);

            $removeEvent = EventFactory::createRenownRemovedFromLocationEvent(
                $attachment->ControllerId,
                $fromLocation,
                1,
                $attachment->getInjectCode()
            );
            $removeEvent->batchId = $batchId;
            $game->theah->eventCheck($removeEvent);
            $game->theah->queueEvent($removeEvent);

            $addEvent = EventFactory::createRenownAddedToLocationEvent(
                $attachment->ControllerId,
                $location,
                1,
                $attachment->getInjectCode(),
                $isMove = true
            );
            $addEvent->batchId = $batchId;
            $game->theah->eventCheck($addEvent);
            $game->theah->queueEvent($addEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($attachment->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }

    private function currentLocationHasRenown(Theah $theah, Character $owner): bool
    {
        if (! $theah->locationInCity($owner->Location))
        {
            return false;
        }

        return $theah->getCityLocation($owner->Location)->Renown > 0;
    }

    /**
     * Other City locations (not Home — printed text says City).
     *
     * @return list<string>
     */
    private function getValidDestinations(Theah $theah, Character $performer): array
    {
        $locations = [];
        foreach ($theah->getCityLocations() as $location)
        {
            if ($location->Name != $performer->Location)
            {
                $locations[] = $location->Name;
            }
        }

        return $locations;
    }
}
