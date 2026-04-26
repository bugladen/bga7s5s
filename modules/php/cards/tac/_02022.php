<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_GainLethal;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;

class _02022 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Lord Stranahan III');
        $this->Image = '02022.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 22;

        $this->initializeFaction('Montaigne');
        $this->Title = clienttranslate('Sly Old Dog');
        $this->Resolve = 5;
        $this->Combat = 1;
        $this->Finesse = 3;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate('Scoundrel'),
            clienttranslate('Musketeer'),
            clienttranslate('Duelist'),
            clienttranslate('Montaigne')
        ];

        $this->Text = clienttranslate("<p>When a challenge is issued to your <b>Diplomat</b> at this location, wound the challenging character.</p><p>Your <b>Musketeers</b> at Stranahan's location gain '<b>Technique:</b> Gain Lethal.'</p>");

        $this->resetCard();

        $technique = new Technique_GainLethal();
        $technique->setId("Technique_02022");
        $this->Techniques = [
            $technique
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventChallengeIssued)
        {
            $defender = $event->theah->getCharacterById($event->defenderId);
            if ($defender->ControllerId == $this->ControllerId && $defender->Location == $this->Location && $defender->hasTrait("Diplomat"))
            {
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($event->challengerId, $this->Id, 1, $this->getInjectCode());
                $event->theah->queueEvent($woundEvent);
            }
        }

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
                $technique = new Technique_GainLethal();
                $technique->setId("Technique_02022");
                $technique->setOwnerId($character->Id);
                $character->addTechnique($technique, $event->theah->game);
                $character->IsUpdated = true;
            }
        }

        if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->Id)
        {
            if ($this->Location != Game::LOCATION_PLAYER_HOME)
            {
                $characters = $event->theah->getCharactersAtLocation($this->Location);
                $characters = array_filter($characters, fn($character) =>
                    $character->Id != $this->Id &&
                    $character->ControllerId == $this->ControllerId &&
                    $character->hasTrait("Musketeer"));

                foreach ($characters as $character)
                {
                    if ($character instanceof IHasTechniques)
                    {
                        $technique = $character->getTechniqueByClassId("Technique_02022");
                        if ($technique)
                        {
                            $character->removeTechnique($technique, $event->theah->game);
                            $character->IsUpdated = true;
                        }
                    }
                }
            }
        }

        if ($event instanceof EventCardMoved)
        {
            //Handle the case where Stranahan is moved to a new location.
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
                            $technique = $character->getTechniqueByClassId("Technique_02022");
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
                        $technique = new Technique_GainLethal();
                        $technique->setId("Technique_02022");
                        $technique->setOwnerId($character->Id);
                        if ($character instanceof IHasTechniques)
                        {
                            $character->addTechnique($technique, $event->theah->game);
                            $character->IsUpdated = true;
                        }
                    }
                }
            }
            //Handle the case where Musketeer is moved to Stranahan's location.
            else if ($event->toLocation == $this->Location && $event->toLocation != Game::LOCATION_PLAYER_HOME)
            {
                $character = $event->theah->getCardById($event->cardId);
                if ($character->ControllerId == $this->ControllerId && $character->hasTrait("Musketeer") && $character instanceof IHasTechniques)
                {
                    $technique = new Technique_GainLethal();
                    $technique->setId("Technique_02022");
                    $technique->setOwnerId($character->Id);
                    $character->addTechnique($technique, $event->theah->game);
                    $character->IsUpdated = true;
                }
            }
            //Handle the case where Musketeer is moved from Stranahan's location.
            else if ($event->fromLocation == $this->Location && $event->fromLocation != Game::LOCATION_PLAYER_HOME)
            {
                $character = $event->theah->getCardById($event->cardId);
                if ($character->ControllerId == $this->ControllerId && $character->hasTrait("Musketeer") && $character instanceof IHasTechniques)
                {
                    $technique = $character->getTechniqueByClassId("Technique_02022");
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