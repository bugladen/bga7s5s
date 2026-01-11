<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;

class _01102 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Unfortunate");
        $this->Image = "01102.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction("Castille");
        
        $this->WealthCost = 0;
        $this->Riposte = 1;
        $this->Parry = 0;
        $this->Thrust = 3;

        $this->Traits = [
            'Hubris',
        ];

        $this->resetCard();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelEndOfRound && $this->ControllerId == $event->playerId && $this->Location == Game::LOCATION_DUELING_LINE)
        {
            $game = $event->theah->game;
            $adversaryId = $event->theah->getDuelOpponentId($event->actorId);
            $adversary = $event->theah->getCharacterById($adversaryId);
            $game->createRiskAttachment($game, "01102_Attachment", $this->Id, $adversary->Location, $this->ControllerId, $adversary->ControllerId, $adversary->Id);
        }
    }
}

