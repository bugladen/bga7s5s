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

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $availableLocations = [];
        $locations = $theah->getCityLocations();
        foreach ($locations as $location)
        {
            if ($location->Controller == 0)
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
                $characters = array_filter($characters, fn($character) => $character->hasTrait("Mercenary"));
                $mercenaryCount = count($characters);
                if ($mercenaryCount == $totalCharacterCount)
                {
                    $availableLocations[] = $location->Name;
                }
            }
        }

        return count($availableLocations) > 0;
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
            $availableLocations = [];
            $locations = $game->theah->getCityLocations();
            foreach ($locations as $location)
            {
                if ($location->Controller == 0)
                {
                    continue;
                }
    
                $characters = $game->theah->getCharactersAtLocation($location->Name);
                if (count($characters) == 0)
                {
                    $availableLocations[] = $location->Name;
                }
                else
                {
                    $totalCharacterCount = count($characters);
                    $characters = array_filter($characters, fn($character) => $character->hasTrait("Mercenary"));
                    $mercenaryCount = count($characters);
                    if ($mercenaryCount == $totalCharacterCount)
                    {
                        $availableLocations[] = $location->Name;
                    }
                }
            }

            $args['locationIds'] = $availableLocations;
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01086)
        {
            $location = $game->theah->getCityLocation($ids[0]);
            $game->setControllerForLocation($location->Name, 0);
            $location->Controller = 0;

            $game->notifyAllPlayers("locationUncontrolled", clienttranslate('${location_name} is now uncontrolled.'), [
                "i18n" => ["location_name"],
                "location_name" => $location->Name,
                "location" => $location->Name,
            ]);

            $game->gamestate->nextState("locationChosen");
        }
    }
}