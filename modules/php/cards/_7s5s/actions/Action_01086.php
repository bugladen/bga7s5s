<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01086 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Make Location Uncontrolled");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getEligibleLocations($theah, $playerId)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01086", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01086)
        {
            $args['locationIds'] = $this->getEligibleLocations($game->theah, (int)$game->getActivePlayerId());
        }

        return $args;
    }

    private function getEligibleLocations(Theah $theah, int $playerId): array
    {
        $availableLocations = [];
        foreach ($theah->getCityLocations() as $location)
        {
            if ($location->Controller == 0)
            {
                continue;
            }

            if ( ! $theah->canLocationBecomeUncontrolledBy($playerId, $location->Name))
            {
                continue;
            }

            if ($this->locationIsEmptyOrOnlyMercenaries($theah, $location->Name))
            {
                $availableLocations[] = $location->Name;
            }
        }

        return $availableLocations;
    }

    /** Card text: "Target a location with no characters or only Mercenaries". */
    private function locationIsEmptyOrOnlyMercenaries(Theah $theah, string $locationName): bool
    {
        $characters = $theah->getCharactersAtLocation($locationName);
        if (count($characters) == 0)
        {
            return true;
        }

        $mercenaryCount = count(array_filter($characters, fn($character) => $character->hasTrait("Mercenary")));
        return $mercenaryCount == count($characters);
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01086)
        {
            $location = $ids[0];
            $owner = $this->getOwningCard($game->theah);

            // WHY: canLocationBecomeUncontrolledBy only checks the Indomitable-style flag.
            // Re-check empty/Mercenary here so a crafted client cannot uncontrol a busy location.
            if ( ! $game->theah->locationInCity($location))
            {
                throw new UserException($game->translate("Invalid location."));
            }

            $cityLocation = $game->theah->getCityLocation($location);
            if ($cityLocation->Controller == 0)
            {
                throw new UserException($game->translate("Location is already uncontrolled."));
            }

            if ( ! $this->locationIsEmptyOrOnlyMercenaries($game->theah, $location))
            {
                throw new UserException($game->translate("Location must have no characters or only Mercenaries."));
            }

            if ($game->theah->canLocationBecomeUncontrolledBy($owner->ControllerId, $location))
            {
                $event = EventFactory::createLocationBecomesUncontrolledEvent($owner->ControllerId, $location);
                $game->theah->queueEvent($event);
            }
            else
            {
                // WHY: still resolve (cost paid) when Indomitable Will etc. blocks mid-resolution.
                $game->notify->all("message", clienttranslate('${location} cannot become uncontrolled.'), [
                    'i18n' => ['location'],
                    'location' => $location,
                ]);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }
}