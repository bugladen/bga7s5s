<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Overcoat;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;

class _05Overcoat extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Varvara Kurakina";
        $this->Title = "Sovereign Sentinal";
        $this->Image = "05Overcoat.v4.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 9;

        $this->initializeFaction("Ussura");

        $this->InPlayXImageOffset = 15;

        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->Traits = [
            "Diplomat",
            "Academic",
            "Magistrate",
            "Ussura"
        ];

        $this->Text = "<p><i>Covert</i> - [Influence] pressures at this location cannot succeed.</p>
<p><b>Covert Action:</b> Engage Varvara • Pressure this location with Resolve. If successful, either move an adjacent Renown to this location or claim it.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_05Overcoat(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY: Covert — Influence pressures at Varvara's uncontrolled location cannot succeed.
        // Valeri flag shape (EventPressureOccuring → PRESSURE_TYPE); pressureLocation forces fail.
        // Gate Influence in pressureTypes so her own Resolve Covert Action is unaffected.
        // Uncontrolled gate: Covert keyword (Valeri parenthetical).
        if ($event instanceof EventPressureOccuring)
        {
            if (! $this->isControlled())
            {
                return;
            }

            if ($event->theah->game->characterIsInDiscardOrLocker($this))
            {
                return;
            }

            if (! $event->theah->cardInCity($this))
            {
                return;
            }

            if ($event->location != $this->Location)
            {
                return;
            }

            if (! in_array(Game::STAT_INFLUENCE, $event->pressureTypes, true))
            {
                return;
            }

            $cityLocation = $event->theah->getCityLocation($this->Location);
            if ($cityLocation === null || $cityLocation->isControlled())
            {
                return;
            }

            $game = $event->theah->game;
            $game->notify->all("message", '${card_inject_code} (Covert): [Influence] pressures at this uncontrolled location cannot succeed.', [
                'card_inject_code' => $this->getInjectCode(),
            ]);

            $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::OVERCOAT_PRESSURE_TYPE);
        }
    }
}
