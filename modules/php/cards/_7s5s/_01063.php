<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01063;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01063Swap;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;

class _01063 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Bastian Girard");
        $this->Image = "img/cards/7s5s/063.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 63;

        $this->initializeFaction("Montaigne");
        $this->Title = "Worldly Wit";
        $this->Resolve = 3;
        $this->Combat = 2;
        $this->Finesse = 4;
        $this->Influence = 1;

        $this->Traits = [
            "Duelist",
            "Musketeer",
            "Montaigne",
        ];

        $this->resetCard();

        $this->Techniques = [
            new Technique_01063(),
        ];

    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterRecruited)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character->ControllerId == $this->ControllerId && $character->Location == $this->Location && $character instanceof IHasTechniques)
            {
                $technique = new Technique_01063Swap();
                $technique->setId("Technique_01063Swap");
                $technique->setOwnerId($character->Id);
                $character->addTechnique($technique, $event->theah->game);
                $character->IsUpdated = true;
            }
        }

        if ($event instanceof EventCardMoved)
        {
            //Handle the case where Bastien is moved to a new location.
            if ($event->cardId == $this->Id)
            {
                //Remove the technique from any characters in the old location.
                if ($event->fromLocation != Game::LOCATION_PLAYER_HOME)
                {
                    $characters = $event->theah->getCharactersAtLocation($event->fromLocation);
                    $characters = array_filter($characters, fn($character) => 
                        $character->Id != $this->Id && 
                        $character->ControllerId == $this->ControllerId);

                    foreach ($characters as $character)
                    {
                        if ($character instanceof IHasTechniques)
                        {
                            $technique = $character->getTechniqueByClassId("Technique_01063Swap");
                            if ($technique)
                            {
                                $character->removeTechnique($technique, $event->theah->game);
                                $character->IsUpdated = true;
                            }
                        }
                    }
                }

                //Add the technique to any characters in the new location.
                if ($event->toLocation != Game::LOCATION_PLAYER_HOME)
                {
                    $characters = $event->theah->getCharactersAtLocation($event->toLocation);
                    $characters = array_filter($characters, fn($character) => 
                        $character->Id != $this->Id && 
                        $character->ControllerId == $this->ControllerId);
                    foreach ($characters as $character)
                    {
                        $technique = new Technique_01063Swap();
                        $technique->setId("Technique_01063Swap");
                        $technique->setOwnerId($character->Id);
                        if ($character instanceof IHasTechniques)
                        {
                            $character->addTechnique($technique, $event->theah->game);
                            $character->IsUpdated = true;
                        }
                    }
                }
            }
            //Handle the case where character is moved to Bastien's location.
            else if ($event->toLocation == $this->Location && $event->toLocation != Game::LOCATION_PLAYER_HOME)
            {
                $character = $event->theah->getCardById($event->cardId);
                if ($character->ControllerId == $this->ControllerId && $character instanceof IHasTechniques)
                {
                    $technique = new Technique_01063Swap();
                    $technique->setId("Technique_01063Swap");
                    $technique->setOwnerId($character->Id);
                    $character->addTechnique($technique, $event->theah->game);
                    $character->IsUpdated = true;
                }
            }
            //Handle the case where character is moved from Bastien's location.
            else if ($event->fromLocation == $this->Location && $event->fromLocation != Game::LOCATION_PLAYER_HOME)
            {
                $character = $event->theah->getCardById($event->cardId);
                if ($character->ControllerId == $this->ControllerId && $character instanceof IHasTechniques)
                {
                    $technique = $character->getTechniqueByClassId("Technique_01063Swap");
                    if ($technique)
                    {
                        $character->removeTechnique($technique, $event->theah->game);
                        $character->IsUpdated = true;
                    }
                }
            }
        }        
    }
}