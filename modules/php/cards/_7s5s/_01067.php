<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01067;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneRiposte;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;

class _01067 extends Character 
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Jean Urbain");
        $this->Image = "01067.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 67;

        $this->initializeFaction("Montaigne");
        $this->Title = clienttranslate("Commander and Confidant");
        $this->Resolve = 4;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->Traits = [
            clienttranslate("Duelist"),
            clienttranslate("Musketeer"),
            clienttranslate("Montaigne"),
        ];

        $this->Text = clienttranslate("<p>Your other Musketeers at Jean's location gain \"Technique: +1 Riposte.\"</p><p>Technique: +1[Thrust]. If you control another Musketeer at this location, you may +1[Riposte] instead.</p>");

        $this->resetCard();
        
        $this->Techniques = [
            new Technique_01067(),
        ];

    }


    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterRecruited)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character->Id != $this->Id &&
                $character->ControllerId == $this->ControllerId && 
                $character->Location == $this->Location && 
                $character->Location != Game::LOCATION_PLAYER_HOME &&
                $character->hasTrait("Musketeer") && 
                $character instanceof IHasTechniques)
            {
                $technique = new Technique_PlusOneRiposte();
                $technique->setId("Technique_01067");
                $technique->setOwnerId($character->Id);
                $character->addTechnique($technique, $event->theah->game);
                $character->IsUpdated = true;
            }
        }

        if ($event instanceof EventCardMoved)
        {
            //Handle the case where Jean is moved to a new location.
            if ($event->cardId == $this->Id)
            {
                //Remove the technique from any musketeers in the old location.
                if ($event->fromLocation != Game::LOCATION_PLAYER_HOME)
                {
                    $characters = $event->theah->getCharactersAtLocation($event->fromLocation);
                    $characters = array_filter($characters, fn($character) => 
                        $character->Id != $this->Id && 
                        $character->ControllerId == $this->ControllerId && 
                        $character->hasTrait("Musketeer"));

                    foreach ($characters as $character)
                    {
                        if ($character instanceof IHasTechniques)
                        {
                            $technique = $character->getTechniqueByClassId("Technique_01067");
                            if ($technique)
                            {
                                $character->removeTechnique($technique, $event->theah->game);
                                $character->IsUpdated = true;
                            }
                        }
                    }
                }

                //Add the technique to any musketeers in the new location.
                if ($event->toLocation != Game::LOCATION_PLAYER_HOME)
                {
                    $characters = $event->theah->getCharactersAtLocation($event->toLocation);
                    $characters = array_filter($characters, fn($character) => 
                        $character->Id != $this->Id && 
                        $character->ControllerId == $this->ControllerId && 
                        $character->hasTrait("Musketeer"));
                    foreach ($characters as $character)
                    {
                        $technique = new Technique_PlusOneRiposte();
                        $technique->setId("Technique_01067");
                        $technique->setOwnerId($character->Id);
                        if ($character instanceof IHasTechniques)
                        {
                            $character->addTechnique($technique, $event->theah->game);
                            $character->IsUpdated = true;
                        }
                    }
                }
            }
            //Handle the case where Musketeer is moved to Jean Urbain's location.
            else if ($event->toLocation == $this->Location && $event->toLocation != Game::LOCATION_PLAYER_HOME)
            {
                $character = $event->theah->getCardById($event->cardId);
                if ($character->ControllerId == $this->ControllerId && $character->hasTrait("Musketeer") && $character instanceof IHasTechniques)
                {
                    $technique = new Technique_PlusOneRiposte();
                    $technique->setId("Technique_01067");
                    $technique->setOwnerId($character->Id);
                    $character->addTechnique($technique, $event->theah->game);
                    $character->IsUpdated = true;
                }
            }
            //Handle the case where Musketeer is moved from Jean Urbain's location.
            else if ($event->fromLocation == $this->Location && $event->fromLocation != Game::LOCATION_PLAYER_HOME)
            {
                $character = $event->theah->getCardById($event->cardId);
                if ($character->ControllerId == $this->ControllerId && $character->hasTrait("Musketeer") && $character instanceof IHasTechniques)
                {
                    $technique = $character->getTechniqueByClassId("Technique_01067");
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