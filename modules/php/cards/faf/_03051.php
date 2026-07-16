<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03051;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03051;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;

class _03051 extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Yepikhodov Yepikvidich");
        $this->Title = clienttranslate("Unreliable Narrator");
        $this->Image = '03051.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 51;

        $this->InPlayXImageOffset = -20;

        $this->initializeFaction("Ussura");

        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate("Scoundrel"),
            clienttranslate("Cossack"),
            clienttranslate("Squire"),
            clienttranslate("Ussura")
        ];

        $this->Text = clienttranslate("<p><b>City Action:</b> Move Yepikhodov to your <b>Leader</b>'s location. Then, en garde your attachment there.</p>
<p>Your other characters at this location gain: <b>Technique:</b> Engage Yepikhodov's attachment • Copy the effects of a <b>Technique</b> on that attachment.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03051(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // Mirror Jean Urbain (_01067): grant/remove location Technique aura.
        // WHY no trait filter: text is "Your other characters", not a subset.

        if ($event instanceof EventCharacterRecruited)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character->Id != $this->Id &&
                $character->ControllerId == $this->ControllerId &&
                $character->Location == $this->Location &&
                $character->Location != Game::LOCATION_PLAYER_HOME &&
                $character instanceof IHasTechniques)
            {
                $this->grantTechniqueTo($character, $event->theah->game);
            }
        }

        if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->Id)
        {
            if ($this->Location != Game::LOCATION_PLAYER_HOME)
            {
                $characters = $event->theah->getCharactersAtLocation($this->Location);
                $characters = array_filter($characters, fn($character) =>
                    $character->Id != $this->Id &&
                    $character->ControllerId == $this->ControllerId);

                foreach ($characters as $character)
                {
                    $this->removeTechniqueFrom($character, $event->theah->game);
                }
            }
        }

        if ($event instanceof EventCardMoved)
        {
            // Yepikhodov moved to a new location.
            if ($event->cardId == $this->Id)
            {
                if ($event->fromLocation != Game::LOCATION_PLAYER_HOME)
                {
                    $characters = $event->theah->getCharactersAtLocation($event->fromLocation);
                    $characters = array_filter($characters, fn($character) =>
                        $character->Id != $this->Id &&
                        $character->ControllerId == $this->ControllerId);

                    foreach ($characters as $character)
                    {
                        $this->removeTechniqueFrom($character, $event->theah->game);
                    }
                }

                if ($event->toLocation != Game::LOCATION_PLAYER_HOME)
                {
                    $characters = $event->theah->getCharactersAtLocation($event->toLocation);
                    $characters = array_filter($characters, fn($character) =>
                        $character->Id != $this->Id &&
                        $character->ControllerId == $this->ControllerId);

                    foreach ($characters as $character)
                    {
                        $this->grantTechniqueTo($character, $event->theah->game);
                    }
                }
            }
            // Another character moved to Yepikhodov's location.
            else if ($event->toLocation == $this->Location && $event->toLocation != Game::LOCATION_PLAYER_HOME)
            {
                $character = $event->theah->getCardById($event->cardId);
                if ($character instanceof Character
                    && $character->ControllerId == $this->ControllerId
                    && $character instanceof IHasTechniques)
                {
                    $this->grantTechniqueTo($character, $event->theah->game);
                }
            }
            // Another character left Yepikhodov's location.
            else if ($event->fromLocation == $this->Location && $event->fromLocation != Game::LOCATION_PLAYER_HOME)
            {
                $character = $event->theah->getCardById($event->cardId);
                if ($character instanceof Character
                    && $character->ControllerId == $this->ControllerId
                    && $character instanceof IHasTechniques)
                {
                    $this->removeTechniqueFrom($character, $event->theah->game);
                }
            }
        }
    }

    private function grantTechniqueTo(Character $character, Game $game): void
    {
        if (! ($character instanceof IHasTechniques))
        {
            return;
        }

        if ($character->getTechniqueByClassId("Technique_03051"))
        {
            return;
        }

        $technique = new Technique_03051();
        $technique->setId("Technique_03051");
        $technique->setOwnerId($character->Id);
        $character->addTechnique($technique, $game);
        $character->IsUpdated = true;
    }

    private function removeTechniqueFrom(Character $character, Game $game): void
    {
        if (! ($character instanceof IHasTechniques))
        {
            return;
        }

        $technique = $character->getTechniqueByClassId("Technique_03051");
        if ($technique)
        {
            $character->removeTechnique($technique, $game);
            $character->IsUpdated = true;
        }
    }
}
