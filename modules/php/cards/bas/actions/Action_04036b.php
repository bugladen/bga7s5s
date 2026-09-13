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

class Action_04036b extends AttachmentAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Performer to another Location with more Renown");
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
                "04036b",
                $this->Id
            );
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04036b)
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

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04036b)
        {
            $location = $ids[0];
            $owner = $this->getOwningCharacter($game->theah);
            $attachment = $this->getOwningAttachment($game->theah);

            if ($owner === null || $attachment === null)
            {
                throw new UserException($game->translate("Equipped character not found."));
            }

            $validDestinations = $this->getValidDestinations($game->theah, $owner);
            if (! in_array($location, $validDestinations, true))
            {
                throw new UserException(sprintf($game->translate("%s cannot move to this location."), $owner->Name));
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

    /**
     * Locations with strictly more Renown than the performer's current location.
     * Home is omitted: it has no Renown track, so it cannot satisfy "more Renown".
     *
     * @return list<string>
     */
    private function getValidDestinations(Theah $theah, Character $performer): array
    {
        if (! $theah->locationInCity($performer->Location))
        {
            return [];
        }

        $currentRenown = $theah->getCityLocation($performer->Location)->Renown;
        $locations = [];
        foreach ($theah->getCityLocations() as $location)
        {
            if ($location->Name != $performer->Location && $location->Renown > $currentRenown)
            {
                $locations[] = $location->Name;
            }
        }

        return $locations;
    }
}
