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
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

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

        // WHY: Leave-play clear must run even if blanked. Prefer ClassId strip across
        // controlled in-play (Jean shape) — Location may already be Locker on CardSentToLocker.
        if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->Id)
        {
            $this->clearGrantedLethalTechniques($event->theah);
            return;
        }

        if ($event instanceof EventCardSentToLocker && $event->cardId == $this->Id)
        {
            $this->clearGrantedLethalTechniques($event->theah);
            return;
        }

        if ($this->abilitiesAreBlanked())
        {
            return;
        }

        if ($event instanceof EventChallengeIssued)
        {
            $defender = $event->theah->getCharacterById($event->defenderId);
            if ($defender->ControllerId == $this->ControllerId && $defender->Location == $this->Location && $defender->hasTrait("Diplomat"))
            {
                // WHY: abilityId = card Id (Joern `_03015` shape). Card-level Forced/
                // passives are not Action/Reaction/Technique composites, so Cascade /
                // Cooper / Spaulders fall back to source ControllerId when getAbilityById
                // misses. Empty abilityId would be treated as threat / non-ability.
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                    $event->challengerId,
                    $this->Id,
                    1,
                    $this->getInjectCode(),
                    (string) $this->Id
                );
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
                $this->grantLethalTechniqueTo($character, $event->theah->game);
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
                        $this->removeLethalTechniqueFrom($character, $event->theah->game);
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
                        $this->grantLethalTechniqueTo($character, $event->theah->game);
                    }
                }
            }
            //Handle the case where Musketeer is moved to Stranahan's location.
            else if ($event->toLocation == $this->Location && $event->toLocation != Game::LOCATION_PLAYER_HOME)
            {
                $character = $event->theah->getCardById($event->cardId);
                if ($character->ControllerId == $this->ControllerId && $character->hasTrait("Musketeer") && $character instanceof IHasTechniques)
                {
                    $this->grantLethalTechniqueTo($character, $event->theah->game);
                }
            }
            //Handle the case where Musketeer is moved from Stranahan's location.
            else if ($event->fromLocation == $this->Location && $event->fromLocation != Game::LOCATION_PLAYER_HOME)
            {
                $character = $event->theah->getCardById($event->cardId);
                if ($character->ControllerId == $this->ControllerId && $character->hasTrait("Musketeer") && $character instanceof IHasTechniques)
                {
                    $this->removeLethalTechniqueFrom($character, $event->theah->game);
                }
            }
        }
    }

    private function grantLethalTechniqueTo(Character $character, Game $game): void
    {
        if (! ($character instanceof IHasTechniques))
        {
            return;
        }
        if ($character->getTechniqueByClassId("Technique_02022"))
        {
            return;
        }

        $technique = new Technique_GainLethal();
        $technique->setId("Technique_02022");
        $technique->setOwnerId($character->Id);
        $character->addTechnique($technique, $game);
        $character->IsUpdated = true;
    }

    private function removeLethalTechniqueFrom(Character $character, Game $game): void
    {
        if (! ($character instanceof IHasTechniques))
        {
            return;
        }

        $technique = $character->getTechniqueByClassId("Technique_02022");
        if ($technique)
        {
            $character->removeTechnique($technique, $game);
            $character->IsUpdated = true;
        }
    }

    private function clearGrantedLethalTechniques(Theah $theah): void
    {
        $controllerId = $this->ControllerId;
        if ($controllerId <= 0)
        {
            return;
        }

        foreach ($theah->getCharactersInPlayByPlayerId($controllerId) as $character)
        {
            if ($character->Id == $this->Id)
            {
                continue;
            }
            $this->removeLethalTechniqueFrom($character, $theah->game);
        }
    }

    // WHY: Fate's Silence skips handleEvent — granted Gain Lethal Techniques stick on allies.
    public function onAbilitiesBlanked(Theah $theah): void
    {
        $this->clearGrantedLethalTechniques($theah);
    }

    public function onAbilitiesUnblanked(Theah $theah): void
    {
        if ($this->IsDying || $this->ControllerId <= 0 || $theah->game->characterIsInDiscardOrLocker($this))
        {
            return;
        }
        if ($this->Location == Game::LOCATION_PLAYER_HOME)
        {
            return;
        }

        foreach ($theah->getCharactersAtLocation($this->Location) as $character)
        {
            if ($character->Id == $this->Id
                || $character->ControllerId != $this->ControllerId
                || ! $character->hasTrait("Musketeer"))
            {
                continue;
            }
            $this->grantLethalTechniqueTo($character, $theah->game);
        }
    }
}