<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01041;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01041 extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Rosine Friese");
        $this->Image = "01041.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 41;

        $this->initializeFaction("Eisen");
        $this->Title = "Esoteric Pathologist";
        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->Traits = [
            "Academic",
            "Eisen",
        ];

        $this->Text = "<p>While Rosine is opposing a Sorcerer, she gains +1 [inf].</p><p>City Action: Target an opposing non-Leader character with equal or lower [Influence] • Engage them. If they are a Sorcerer, move them Home.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_01041(),
        ];
    }

    private function updateInfluence(Theah $theah, int $adjustment = 0)
    {

        $influenceEvent = EventFactory::createCharacterInfluenceModifiedEvent(
            $this->ControllerId, 
            $this->Id, 
            $this->ModifiedInfluence, 
            $this->ModifiedInfluence + $adjustment, 
            $this->getInjectCode()
        );
        $theah->queueEvent($influenceEvent);
    }

    public function getOpposingSorcererCount(Theah $theah, string $location): int
    {
        $sorcerers = $theah->getCharactersAtLocation($location);
        $sorcerers = array_filter($sorcerers, fn($character) => $character->hasTrait("Sorcerer") && $character->isNotControlledByPlayer($this->ControllerId));
        return count($sorcerers);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardMoved && $event->cardId == $this->Id && $event->fromLocation != Game::LOCATION_PLAYER_HOME)
        {
            if ($this->getOpposingSorcererCount($event->theah, $event->fromLocation) == 0 && $this->getOpposingSorcererCount($event->theah, $event->toLocation) >= 1)
            {
                $this->updateInfluence($event->theah, 1);
            }
            else if ($this->getOpposingSorcererCount($event->theah, $event->fromLocation) >= 1 && $this->getOpposingSorcererCount($event->theah, $event->toLocation) == 0)
            {
                $this->updateInfluence($event->theah, -1);
            }
        }

        if ($event instanceof EventCardMoved && $event->cardId != $this->Id && $event->toLocation == $this->Location)
        {
            $character = $event->theah->getCharacterById($event->cardId);
            if ($character->hasTrait("Sorcerer") && $character->ControllerId != $this->ControllerId && $this->getOpposingSorcererCount($event->theah, $event->toLocation) == 0)
            {
                $this->updateInfluence($event->theah, 1);
            }
        }
        
        if ($event instanceof EventCardMoved && $event->cardId != $this->Id && $event->fromLocation == $this->Location)
        {
            $character = $event->theah->getCharacterById($event->cardId);
            if ($character->hasTrait("Sorcerer") && $character->ControllerId != $this->ControllerId && $this->getOpposingSorcererCount($event->theah, $this->Location) == 1)
            {
                $this->updateInfluence($event->theah, -1);
            }
        }

        if ($event instanceof EventCharacterMustered && $event->characterId == $this->Id)
        {
            if ($this->getOpposingSorcererCount($event->theah, $event->location) >= 1)
            {
                $this->updateInfluence($event->theah, 1);
            }
        }

        if ($event instanceof EventCharacterMustered && $event->characterId != $this->Id)
        {
            if ($this->getOpposingSorcererCount($event->theah, $event->location) == 1)
            {
                $this->updateInfluence($event->theah, 1);
            }
        }

        if ($event instanceof EventCharacterDestroyed && $event->characterId != $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character->Location == $this->Location && $this->getOpposingSorcererCount($event->theah, $this->Location) == 1)
            {
                $this->updateInfluence($event->theah, -1);
            }
        }

        if ($event instanceof EventCharacterRecruited)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character->Location == $this->Location && $this->getOpposingSorcererCount($event->theah, $this->Location) == 0)
            {
                $this->updateInfluence($event->theah, 1);
            }
        }
    }
}
