<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02029 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Claim Location (Diplomats)");
        $this->RequiresPerformerSelected = true;
    }

    private function getDiplomatsAtLocation(string $location, int $playerId, Theah $theah): array
    {
        $characters = $theah->getCharactersAtLocationByPlayerId($location, $playerId);
        return array_values(array_filter($characters, fn($c) => $c->hasTrait("Diplomat")));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $diplomats = $theah->getCharactersInCityByPlayerId($playerId);
        $diplomats = array_filter($diplomats, fn($c) => $c->hasTrait("Diplomat"));

        $checkedLocations = [];
        foreach ($diplomats as $diplomat)
        {
            if (in_array($diplomat->Location, $checkedLocations))
            {
                continue;
            }
            $checkedLocations[] = $diplomat->Location;

            $diplomatsAtLocation = $this->getDiplomatsAtLocation($diplomat->Location, $playerId, $theah);
            $location = $theah->getCityLocation($diplomat->Location);
            if (count($diplomatsAtLocation) > $location->Renown)
            {
                return true;
            }
        }

        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $diplomats = $theah->getCharactersInCityByPlayerId($playerId);
        $diplomats = array_filter($diplomats, fn($c) => $c->hasTrait("Diplomat"));

        $performers = [];
        foreach ($diplomats as $diplomat)
        {
            $diplomatsAtLocation = $this->getDiplomatsAtLocation($diplomat->Location, $playerId, $theah);
            $location = $theah->getCityLocation($diplomat->Location);
            if (count($diplomatsAtLocation) > $location->Renown)
            {
                $performers[] = $diplomat;
            }
        }

        return array_values($performers);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            $claimEvent = EventFactory::createLocationClaimedEvent($performer->ControllerId, $performerId, $performer->Location);
            $event->theah->queueEvent($claimEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}
