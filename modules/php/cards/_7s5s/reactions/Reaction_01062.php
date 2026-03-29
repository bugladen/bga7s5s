<?php

    namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeAccepted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

    class Reaction_01062 extends CardReaction
    {
        public function __construct()
        {
            parent::__construct();

            $this->Name = clienttranslate("Move Adjacent Renown to Odette's Location");
        }

        public function getReactionDescription(Theah $theah): string
        {
            return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to move adjacent Renown to Odette\'s location: ');
        }

        public function getReactionButtonProperties(Theah $theah): array
        {
            $array = parent::getReactionButtonProperties($theah);

            $odette = $this->getOwningCharacter($theah);
            $adjacentLocations = $theah->getAdjacentCityLocations($odette->Location, $includeHome = false);
            foreach ($adjacentLocations as $locationName)
            {
                $location = $theah->getCityLocation($locationName);
                if ($location->Renown > 0)
                {
                    $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Move Renown from %s'), $locationName), "moveFrom-$locationName");
                }
            }
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

            return $array;
        }

        public function handleEvent(Event $event)
        {
            parent::handleEvent($event);

            if ($event instanceof EventChallengeAccepted && $this->isAvailable())
            {
                $odette = $this->getOwningCharacter($event->theah);
    
                if ($odette->Location != Game::LOCATION_PLAYER_HOME)
                {
                    $challenger = $event->theah->getCharacterById($event->challengerId);

                    if ($challenger->Location == $odette->Location)
                    {
                        $adjacentLocations = $event->theah->getAdjacentCityLocations($odette->Location, $includeHome = false);
                        $renownAtLocations = false;
                        foreach ($adjacentLocations as $locationName)
                        {
                            $location = $event->theah->getCityLocation($locationName);
                            if ($location->Renown > 0)
                            {
                                $renownAtLocations = true;
                            }
                        }
                        
                        if ($renownAtLocations)
                        {
                            $reactionEvent = EventFactory::createReactionTransitionEvent($odette->ControllerId, $odette->Id, $this->Id);
                            $event->theah->queueEvent($reactionEvent);
                        }
                    }    
                }
            }

            if ($event instanceof EventCharacterIntervened && $this->isAvailable())
            {
                $odette = $this->getOwningCharacter($event->theah);

                if ($odette->Location != Game::LOCATION_PLAYER_HOME)
                {
                    $challenger = $event->theah->getCharacterById($event->newTargetId);

                    if ($challenger->Location == $odette->Location)
                    {
                        $adjacentLocations = $event->theah->getAdjacentCityLocations($odette->Location, $includeHome = false);
                        $renownAtLocations = false;
                        foreach ($adjacentLocations as $locationName)
                        {
                            $location = $event->theah->getCityLocation($locationName);
                            if ($location->Renown > 0)
                            {
                                $renownAtLocations = true;
                            }
                        }
                        
                        if ($renownAtLocations)
                        {
                            $reactionEvent = EventFactory::createReactionTransitionEvent($odette->ControllerId, $odette->Id, $this->Id);
                            $event->theah->queueEvent($reactionEvent);
                        }
                    }    
                }
            }
        }

        public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
        {
            parent::performReaction($game, $state, $internalId, $reactionId);

            if ($reactionId != "pass")
            {
                //Get the location name from the reactionId
                $locationName = str_replace("moveFrom-", "", $reactionId);

                $odette = $this->getOwningCharacter($game->theah);
                $location = $game->theah->getCityLocation($locationName);

                $event = EventFactory::createReknownRemovedFromLocationEvent($odette->ControllerId, $locationName, 1, $odette->getInjectCode());
                $game->theah->eventCheck($event);
                $game->theah->queueEvent($event);

                $event = EventFactory::createReknownAddedToLocationEvent($odette->ControllerId, $odette->Location, 1, $odette->getInjectCode(), $isMove = true);
                $game->theah->eventCheck($event);
                $game->theah->queueEvent($event);

                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction and moved 1 Renown from ${location_name} to ${odette_location}.'), [
                    "i18n" => ["location_name", "odette_location"],
                    "reaction_inject_code" => $odette->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($odette->ControllerId),
                    "location_name" => $locationName,
                    "odette_location" => $odette->Location,
                ]);

                $this->setUsed($game->theah, true);
            }

            $game->gamestate->nextState("done");
        }
    }