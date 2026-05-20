<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

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

            $characters = $theah->getCharactersAtLocation($location->Name);
            if (count($characters) == 0)
            {
                $availableLocations[] = $location->Name;
            }
            else
            {
                $totalCharacterCount = count($characters);
                $mercenaryCount = count(array_filter($characters, fn($character) => $character->hasTrait("Mercenary")));
                if ($mercenaryCount == $totalCharacterCount)
                {
                    $availableLocations[] = $location->Name;
                }
            }
        }

        return $availableLocations;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01086)
        {
            $location = $ids[0];
            $owner = $this->getOwningCard($game->theah);

            if ($game->theah->canLocationBecomeUncontrolledBy($owner->ControllerId, $location))
            {
                $event = EventFactory::createLocationBecomesUncontrolledEvent($owner->ControllerId, $location);
                $game->theah->queueEvent($event);
            }
            else
            {
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