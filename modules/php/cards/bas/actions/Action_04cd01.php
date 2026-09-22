<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04cd01 extends AttachmentAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Engage: Move Equipped Character to Adjacent City Location");
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

        $adjacentLocations = $theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
        return count($adjacentLocations) > 0;
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
                "04cd01",
                $this->Id
            );
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD01)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args["performerId"] = $owner->Id;
            $args["locationIds"] = $game->theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD01)
        {
            $location = $ids[0];
            $attachment = $this->getOwningAttachment($game->theah);
            $owner = $this->getOwningCharacter($game->theah);

            if ($attachment === null || $owner === null)
            {
                throw new UserException($game->translate("Equipped character not found."));
            }

            $locations = $game->theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
            if (! in_array($location, $locations))
            {
                throw new UserException(sprintf($game->translate("Location is not adjacent to %s."), $owner->Location));
            }

            // WHY: printed cost is engage this attachment, not the performer.
            $engageEvent = EventFactory::createCardEngagedEvent(
                $attachment->ControllerId,
                $attachment->Id,
                $attachment->Id,
                $this->Id
            );
            $game->theah->queueEvent($engageEvent);

            // WHY engage=false: move is the effect; attachment already paid the engage cost.
            $moveEvent = EventFactory::createCardMovingEvent(
                $owner->ControllerId,
                $owner->Id,
                $owner->Location,
                $location,
                false,
                $attachment->Id,
                $this->Id
            );
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($attachment->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }
}
