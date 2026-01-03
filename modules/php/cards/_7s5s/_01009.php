<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01009;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;

class _01009 extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Cirilo Naucriparos";
        $this->Image = "img/cards/7s5s/009.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 9;

        $this->initializeFaction("Vodacce");
        $this->Title = "Self-serving Serpent";
        $this->Resolve = 4;
        $this->Combat = 3;
        $this->Finesse = 1;
        $this->Influence = 2;

        $this->Traits = [
            "Red Hand",
            "Extortionist",
            "Numa",
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01009(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (($event instanceof EventApproachCharacterPlayed || $event instanceof EventCharacterMustered) && $event->characterId == $this->Id)
        {
            $characters = $event->theah->getCharactersInPlayByPlayerId($event->playerId);
            $characters = array_filter($characters, fn($character) => $character->hasTrait("Mercenary"));
            foreach ($characters as $character)
            {
                $character->addTrait($event->theah->game, "Brute");
            }
        }

        if ($event instanceof EventCharacterRecruited && $event->playerId == $this->ControllerId)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character->hasTrait("Mercenary"))
            {
                $character->addTrait($event->theah->game, "Brute");
            }

        }

        if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->Id)
        {
            $characters = $event->theah->getCharactersInPlayByPlayerId($event->playerId);
            $characters = array_filter($characters, fn($character) => $character->hasTrait("Mercenary"));
            foreach ($characters as $character)
            {
                $character->removeTrait($event->theah->game, "Brute");

                $bruteEvent = EventFactory::createCharacterLostBruteEvent($event->playerId, $character->Id);
                $event->theah->queueEvent($bruteEvent);
            }
        }
    }

}