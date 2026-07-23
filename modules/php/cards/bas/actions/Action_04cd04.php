<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04cd04 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Engage: Make Adjacent Location Uncontrolled and Move There");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);

        // WHY: Printed Action (not City Action) — only once mustered. Uncontrolled city-deck
        // mercenaries would otherwise pass CardAction's parent check for every player.
        if (! $owner->isControlled())
        {
            return false;
        }

        if ($owner->Engaged)
        {
            return false;
        }

        return count($this->getEligibleLocations($theah, $owner->Location, $playerId)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "04cd04", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD04)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args["performerId"] = $owner->Id;
            $args["locationIds"] = $this->getEligibleLocations($game->theah, $owner->Location, $owner->ControllerId);
        }

        return $args;
    }

    /**
     * Adjacent city locations that are currently controlled and may become uncontrolled.
     *
     * @return string[]
     */
    private function getEligibleLocations(Theah $theah, string $fromLocation, int $playerId): array
    {
        $adjacent = $theah->getAdjacentCityLocations($fromLocation, $includeHome = false);
        $eligible = [];

        foreach ($adjacent as $locationName)
        {
            $location = $theah->getCityLocation($locationName);
            if ($location->Controller == 0)
            {
                continue;
            }

            if (! $theah->canLocationBecomeUncontrolledBy($playerId, $locationName))
            {
                continue;
            }

            $eligible[] = $locationName;
        }

        return $eligible;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD04)
        {
            $location = $ids[0];
            $owner = $this->getOwningCharacter($game->theah);

            $locations = $this->getEligibleLocations($game->theah, $owner->Location, $owner->ControllerId);
            if (! in_array($location, $locations))
            {
                throw new UserException(sprintf($game->translate("Location is not a valid adjacent target for %s."), $owner->Name));
            }

            // WHY: Engage is the printed cost; move must not re-engage (Pattern C).
            $engageEvent = EventFactory::createCardEngagedEvent(
                $owner->ControllerId,
                $owner->Id,
                $owner->Id,
                $this->Id
            );
            $game->theah->queueEvent($engageEvent);

            if ($game->theah->canLocationBecomeUncontrolledBy($owner->ControllerId, $location))
            {
                $uncontrolledEvent = EventFactory::createLocationBecomesUncontrolledEvent(
                    $owner->ControllerId,
                    $location
                );
                $game->theah->queueEvent($uncontrolledEvent);
            }
            else
            {
                $game->notify->all("message", clienttranslate('${location} cannot become uncontrolled.'), [
                    'i18n' => ['location'],
                    'location' => $location,
                ]);
            }

            $moveEvent = EventFactory::createCardMovingEvent(
                $owner->ControllerId,
                $owner->Id,
                $owner->Location,
                $location,
                false,
                $owner->Id,
                $this->Id
            );
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }
}
