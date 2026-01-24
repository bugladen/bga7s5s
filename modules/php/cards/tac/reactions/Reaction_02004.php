<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressured;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02004 extends CardReaction
{
    private string $location = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Move Adjacent Performer to Location When Opponent Initiates Pressure');
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('Opponent has Initiated a Pressure. ${you} may choose to move adjacent Performer to Location: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $adjacentLocations = $theah->getAdjacentCityLocations($this->location, $includeHome = false);
        $adjacentCharacters = [];
        $owner = $this->getOwningCard($theah);
        foreach ($adjacentLocations as $locationName)
        {
            $characters = $theah->getCharactersAtLocation($locationName);
            $characters = array_filter($characters, fn($character) => $character->ControllerId == $owner->ControllerId && ! $character->Engaged);
            foreach ($characters as $character)
            {
                $adjacentCharacters[] = $character;
            }
        }

        foreach ($adjacentCharacters as $character)
        {
            $array[] = $this->createButtonProperty($theah->game, $character->Name, "moveTo-$character->Id");
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventPressureOccuring && $this->isAvailable())
        {
            $theah = $event->theah;
            $owner = $this->getOwningCard($theah);
            $adjacentLocations = $theah->getAdjacentCityLocations($event->location, $includeHome = false);
            $adjacentCharacters = [];
            foreach ($adjacentLocations as $locationName)
            {
                $characters = $theah->getCharactersAtLocation($locationName);
                $characters = array_filter($characters, fn($character) => $character->ControllerId == $owner->ControllerId && ! $character->Engaged);
                foreach ($characters as $character)
                {
                    $adjacentCharacters[] = $character;
                }
            }

            if (count($adjacentCharacters) > 0)
            {
                $this->location = $event->location;
                $owner->IsUpdated = true;
    
                $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($transition);
            }
        }

        if ($event instanceof EventLocationPressured && $this->location != '')
        {
            $owner = $this->getOwningCard($event->theah);
            $this->location = '';
            $owner->IsUpdated = true;

            if ($event->success)
            {
                $drawEvent = EventFactory::createCardDrawnEvent($owner->ControllerId, $owner->getInjectCode());
                $event->theah->queueEvent($drawEvent);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != "pass")
        {
            $owner = $this->getOwningCard($game->theah);
            $characterId = str_replace("moveTo-", "", $reactionId);
            $character = $game->theah->getCharacterById($characterId);
            $event = EventFactory::createCardMovingEvent($owner->ControllerId, $character->Id, $character->Location, $this->location, $engage=false, $character->Id, $this->Id);            
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction and moved ${character_inject_code} to ${location_name}.'), [
                "i18n" => ["location_name"],
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_inject_code" => $character->getInjectCode(),
                "location_name" => $this->location,
            ]);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}