<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01062 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move adjacent Duelist to Odette's location");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
            return false;

        $odette = $this->getOwningCharacter($theah);

        if ($odette->Location == Game::LOCATION_PLAYER_HOME)
            return false;

        //Check if there is a duelist adjacent to Odette
        $count = 0;
        $adjacentLocations = $theah->getAdjacentCityLocations($odette->Location);
        foreach ($adjacentLocations as $location)
        {
            $characters = $theah->getCharactersAtLocation($location);
            //Filter duelists owned by controller of Odette
            $characters = array_filter($characters, fn($character) => $character->ControllerId == $odette->ControllerId && in_array("Duelist", $character->Traits));
            $count += count($characters);
        }

        return $count > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $odette = $this->getOwningCharacter($theah);
        $adjacentLocations = $theah->getAdjacentCityLocations($odette->Location);
        $performers = [];
        foreach ($adjacentLocations as $location)
        {
            $characters = $theah->getCharactersAtLocation($location);
            //Filter duelists owned by controller of Odette
            $characters = array_values(array_filter($characters, fn($character) => $character->ControllerId == $odette->ControllerId && in_array("Duelist", $character->Traits)));
            $performers = array_merge($performers, $characters);
        }

        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $odette = $this->getOwningCharacter($event->theah);

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            $game->notifyAllPlayers("message", clienttranslate('${player_name} used the Action from ${odette_inject_code} to move ${performer_inject_code} to ${location_name}.'), [
                "i18n" => ["location_name"],
                "player_name" => $game->getPlayerNameById($performer->ControllerId),
                "odette_inject_code" => $odette->getInjectCode(),
                "performer_inject_code" => $performer->getInjectCode(),
                "location_name" => $odette->Location,
            ]);

            $moveEvent = EventFactory::createCardMovedEvent($event->playerId, $performer->Id, $performer->Location, $odette->Location, $engage = false, $sourceId = $odette->Id);
            $event->theah->eventCheck($moveEvent);
            $event->theah->queueEvent($moveEvent);

            $this->setUsed($event->theah, true);
        }
    }
}