<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Cooper;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques\Technique_05Cooper;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneThrust;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _05Cooper extends Leader implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = "Don Vissenta Scarpa";
        $this->Title = "Crownless Claimant";
        $this->Image = "05Cooper.v3.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 2;

        $this->initializeFaction("Vodacce");

        $this->Resolve = 5;
        $this->Combat = 3;
        $this->Finesse = 3;
        $this->Influence = 3;

        $this->CrewCap = 5;
        $this->Panache = 6;

        $this->Traits = [
            "Leader",
            "Hero",
            "Duelist",
            "Usurper",
            "Vodacce"
        ];

        $this->Text = "<p><i>Reaction</i> - When an opponent's ability wounds Vissenta • Ignore that wound</p>
<p>Your Thugs at this location gain:
<br><b>Technique: +1[Thrust].</b></p>
<p><b>Technique</b>: Wound your Red Hand at this location • +2[Riposte].</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_05Cooper(),
        ];

        // Character already implements IHasTechniques / TechniqueTrait
        $this->Techniques = [
            new Technique_05Cooper(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY: Leave-play clear must run even if blanked. Leader is Silence-immune today
        // (non-Leader equip only) but hooks stay consistent for future blanking sources.
        if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->Id)
        {
            $this->clearGrantedThrustTechniques($event->theah);
            return;
        }

        if ($event instanceof EventCardSentToLocker && $event->cardId == $this->Id)
        {
            $this->clearGrantedThrustTechniques($event->theah);
            return;
        }

        if ($this->abilitiesAreBlanked())
        {
            return;
        }

        // WHY: Jean Urbain (_01067) location Technique grant aura — Thug trait filter;
        // Vissenta is not a Thug so no "other" exclusion needed beyond Id != self.
        // ClassId Technique_05Cooper matches Jean's reuse of the card Technique id string
        // for granted PlusOne* instances (remove via getTechniqueByClassId).

        if ($event instanceof EventCharacterRecruited)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character->Id != $this->Id &&
                $character->ControllerId == $this->ControllerId &&
                $character->Location == $this->Location &&
                $character->Location != Game::LOCATION_PLAYER_HOME &&
                $character->hasTrait("Thug") &&
                $character instanceof IHasTechniques)
            {
                $this->grantThrustTechniqueTo($character, $event->theah->game);
            }
        }

        // WHY: Muster does not emit EventCardMoved (Bastien / Jean gap). Without this,
        // mustering Vissenta onto Thugs (or mustering a Thug onto her) never grants.
        if ($event instanceof EventCharacterMustered)
        {
            if ($event->characterId == $this->Id)
            {
                if ($event->location != Game::LOCATION_PLAYER_HOME)
                {
                    $characters = $event->theah->getCharactersAtLocation($event->location);
                    $characters = array_filter($characters, fn($character) =>
                        $character->Id != $this->Id &&
                        $character->ControllerId == $this->ControllerId &&
                        $character->hasTrait("Thug"));

                    foreach ($characters as $character)
                    {
                        $this->grantThrustTechniqueTo($character, $event->theah->game);
                    }
                }
            }
            else if ($event->location == $this->Location &&
                $event->location != Game::LOCATION_PLAYER_HOME)
            {
                $character = $event->theah->getCharacterById($event->characterId);
                if ($character->ControllerId == $this->ControllerId &&
                    $character->hasTrait("Thug") &&
                    $character instanceof IHasTechniques)
                {
                    $this->grantThrustTechniqueTo($character, $event->theah->game);
                }
            }
        }

        // Leave-play Destroyed / CardSentToLocker handled at top of handleEvent.

        if ($event instanceof EventCardMoved)
        {
            if ($event->cardId == $this->Id)
            {
                if ($event->fromLocation != Game::LOCATION_PLAYER_HOME)
                {
                    $characters = $event->theah->getCharactersAtLocation($event->fromLocation);
                    $characters = array_filter($characters, fn($character) =>
                        $character->Id != $this->Id &&
                        $character->ControllerId == $this->ControllerId &&
                        $character->hasTrait("Thug"));

                    foreach ($characters as $character)
                    {
                        $this->removeThrustTechniqueFrom($character, $event->theah->game);
                    }
                }

                if ($event->toLocation != Game::LOCATION_PLAYER_HOME)
                {
                    $characters = $event->theah->getCharactersAtLocation($event->toLocation);
                    $characters = array_filter($characters, fn($character) =>
                        $character->Id != $this->Id &&
                        $character->ControllerId == $this->ControllerId &&
                        $character->hasTrait("Thug"));

                    foreach ($characters as $character)
                    {
                        $this->grantThrustTechniqueTo($character, $event->theah->game);
                    }
                }
            }
            else if ($event->toLocation == $this->Location && $event->toLocation != Game::LOCATION_PLAYER_HOME)
            {
                $character = $event->theah->getCardById($event->cardId);
                if ($character instanceof Character
                    && $character->ControllerId == $this->ControllerId
                    && $character->hasTrait("Thug")
                    && $character instanceof IHasTechniques)
                {
                    $this->grantThrustTechniqueTo($character, $event->theah->game);
                }
            }
            else if ($event->fromLocation == $this->Location && $event->fromLocation != Game::LOCATION_PLAYER_HOME)
            {
                $character = $event->theah->getCardById($event->cardId);
                if ($character instanceof Character
                    && $character->ControllerId == $this->ControllerId
                    && $character->hasTrait("Thug")
                    && $character instanceof IHasTechniques)
                {
                    $this->removeThrustTechniqueFrom($character, $event->theah->game);
                }
            }
        }
    }

    private function grantThrustTechniqueTo(Character $character, Game $game): void
    {
        if (! ($character instanceof IHasTechniques))
        {
            return;
        }

        // WHY: Dedup — Jean historically re-adds; Yepikhodov prefers skip if present.
        if ($character->getTechniqueByClassId("Technique_05Cooper"))
        {
            return;
        }

        $technique = new Technique_PlusOneThrust();
        $technique->setId("Technique_05Cooper");
        $technique->setOwnerId($character->Id);
        $character->addTechnique($technique, $game);
        $character->IsUpdated = true;
    }

    private function removeThrustTechniqueFrom(Character $character, Game $game): void
    {
        if (! ($character instanceof IHasTechniques))
        {
            return;
        }

        $technique = $character->getTechniqueByClassId("Technique_05Cooper");
        if ($technique)
        {
            $character->removeTechnique($technique, $game);
            $character->IsUpdated = true;
        }
    }

    private function clearGrantedThrustTechniques(Theah $theah): void
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

            $this->removeThrustTechniqueFrom($character, $theah->game);
        }
    }

    // WHY: Fate's Silence skips handleEvent — granted +1 Thrust on Thugs would stick.
    // Leader is Silence-immune today; hooks still required for consistency / future blankers.
    public function onAbilitiesBlanked(Theah $theah): void
    {
        $this->clearGrantedThrustTechniques($theah);
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
                || ! $character->hasTrait("Thug"))
            {
                continue;
            }
            $this->grantThrustTechniqueTo($character, $theah->game);
        }
    }
}
