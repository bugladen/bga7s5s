<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action_01071;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01071 extends Scheme implements IHasActions
{
    use ActionTrait;
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Épée Sanglante");
        $this->Image = "img/cards/7s5s/071.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 71;

        $this->Faction = "Montaigne";
        $this->Initiative = 26;
        $this->PanacheModifier = -1;

        $this->Traits = [
            "Challenge", 
            "Duty",
            "Glory",
        ];

        $this->Actions = [
            new Action_01071(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {

            $event->theah->game->notifyAllPlayers("message", clienttranslate('${scheme_inject_code} now resolves. ${player_name} must choose a city location to place reknown onto.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            //Transition to the state where player can choose any location.
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, '01071');
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventCardMoved && $this->Location == Game::LOCATION_PLAYER_HOME && $event->initiatingPlayerId == $this->ControllerId)
        {
            $character = $event->theah->getCharacterById($event->cardId);
            if (in_array("Musketeer", $character->Traits))
            {
                $addInfluence = false;
                $removeInfluence = false;

                //If from location is home and to location is in the city, +1 Influence
                if ($event->fromLocation == Game::LOCATION_PLAYER_HOME && $event->theah->locationInCity($event->toLocation))
                {
                    $location = $event->theah->getCityLocation($event->toLocation);
                    if ($location->Reknown >= 2)
                    {
                        $addInfluence = true;
                    }
                }

                //If from location is in the city and to location is home, -1 Influence
                else if ($event->theah->locationInCity($event->fromLocation) && $event->toLocation == Game::LOCATION_PLAYER_HOME)
                {
                    $location = $event->theah->getCityLocation($event->fromLocation);
                    if ($location->Reknown >= 2)
                    {
                        $removeInfluence = true;
                    }
                }

                // If both locations are in the city 
                else if ($event->theah->locationInCity($event->fromLocation) && $event->theah->locationInCity($event->toLocation))
                {
                    $oldLocation = $event->theah->getCityLocation($event->fromLocation);
                    $newLocation = $event->theah->getCityLocation($event->toLocation);

                    // If the old location has less than 2 Reknown, 
                    // and the new location has 2 or more Reknown, +1 Influence
                    if ($oldLocation->Reknown < 2 && $newLocation->Reknown >= 2)
                    {
                        $addInfluence = true;
                    }

                    // If both locations are in the city and the old location has 2 or more Reknown, 
                    // and the new location has less than 2 Reknown, remove -1 Influence
                    if ($oldLocation->Reknown >= 2 && $newLocation->Reknown < 2)
                    {
                        $removeInfluence = true;
                    }
                }

                if ($addInfluence)
                {
                    $this->addInfluence($event->theah, $event->initiatingPlayerId, $character);
                }

                if ($removeInfluence)
                {
                    $this->removeInfluence($event->theah, $event->initiatingPlayerId, $character);
                }
            }
        }

        if ($event instanceof EventReknownAddedToLocation && $this->Location == Game::LOCATION_PLAYER_HOME)
        {
            // Do we have any Musketeers in that location?
            $characters = $event->theah->getCharactersAtLocation($event->location);
            $musketeers = array_filter($characters, fn($character) => in_array("Musketeer", $character->Traits));
            if (count($musketeers) > 0)
            {
                $location = $event->theah->getCityLocation($event->location);

                // If the location now has 2 or more Reknown, and before hand less than 2, then add Influence to all Musketeers
                if ($location->Reknown >= 2 && $location->Reknown - $event->amount < 2)
                {
                    foreach ($musketeers as $musketeer)
                    {
                        $this->addInfluence($event->theah, $musketeer->ControllerId, $musketeer);
                    }
                }
            }
        }

        if ($event instanceof EventReknownRemovedFromLocation && $this->Location == Game::LOCATION_PLAYER_HOME)
        {
            $characters = $event->theah->getCharactersAtLocation($event->location);
            $musketeers = array_filter($characters, fn($character) => in_array("Musketeer", $character->Traits));
            if (count($musketeers) > 0)
            {
                $location = $event->theah->getCityLocation($event->location);

                // If the location now has less than 2 Reknown, and before hand had 2 or more, then remove Influence from all Musketeers
                if ($location->Reknown < 2 && $location->Reknown + $event->amount >= 2)
                {
                    foreach ($musketeers as $musketeer)
                    {
                        $this->removeInfluence($event->theah, $musketeer->ControllerId, $musketeer);
                    }
                }
            }
        }
    }

    private function addInfluence(Theah $theah, int $playerId, Character $character)
    {
        $theah->game->notifyAllPlayers("message", clienttranslate('Épée Sanglante: ${character_name} gains 1 Influence.'), [
            'i18n' => ['character_name'],
            "character_name" => $character->Name,
        ]);

        $modifiedEvent = EventFactory::createCharacterInfluenceModifiedEvent(
            $playerId, 
            $character->Id, 
            $character->ModifiedInfluence, 
            $character->ModifiedInfluence + 1);

        $theah->eventCheck($modifiedEvent);
        $theah->queueEvent($modifiedEvent);
    }

    private function removeInfluence(Theah $theah, int $playerId, Character $character)
    {
        $theah->game->notifyAllPlayers("message", clienttranslate('Épée Sanglante: ${character_name} loses 1 Influence.'), [
            'i18n' => ['character_name'],
            "character_name" => $character->Name,
        ]);

        $modifiedEvent = EventFactory::createCharacterInfluenceModifiedEvent(
            $playerId, 
            $character->Id, 
            $character->ModifiedInfluence, 
            $character->ModifiedInfluence - 1);

        $theah->eventCheck($modifiedEvent);
        $theah->queueEvent($modifiedEvent);
    }
}