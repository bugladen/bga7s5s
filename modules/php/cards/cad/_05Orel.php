<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;

class _05Orel extends Character
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "Orel";
        $this->Image = "05Orel.v4.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 11;

        $this->initializeFaction("Ussura");

        $this->Resolve = 2;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->Traits = [
            "Animal",
            "Eagle",
            "Familiar",
        ];

        $this->Text = "<p>Orel cannot be included in Approach decks.</p>
<p>Orel cannot be equipped and cannot perform abilities on risks and schemes.</p>
<p>During pressures at Orel's location, add +1 to your total. If you initiated the pressure, you succeed even if tied.</p>";

        $this->resetCard();
    }

    // WHY: Printed Approach ban — DeckValidator + PutIntoApproachDeck eventCheckCore
    // (Damned / Brute shape). Always false (structural deck rule, not Silence-blankable).
    public function canEnterApproachDeck(): bool
    {
        return false;
    }

    // WHY: Printed "cannot be equipped". When Fate's Silence blanks the text box the
    // restriction lifts (abilitiesAreBlanked → true → can receive).
    public function canReceiveAttachments(): bool
    {
        return $this->abilitiesAreBlanked();
    }

    // WHY: Printed "cannot perform abilities on risks and schemes". Same Silence lift.
    public function canPerformRiskAndSchemeAbilities(): bool
    {
        return $this->abilitiesAreBlanked();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY: Pressure aura — Valeri shape (EventPressureOccuring → PRESSURE_TYPE flag)
        // but NOT Covert: no uncontrolled gate. +1 always for Orel's controller; win-ties
        // only when that controller initiated ($event->playerId).
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

            $game = $event->theah->game;
            $game->notify->all("message", '${card_inject_code} will add +1 to ${player_name}\'s pressure total at this location.', [
                'card_inject_code' => $this->getInjectCode(),
                'player_name' => $game->getPlayerNameById($this->ControllerId),
            ]);

            $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::OREL_PRESSURE_TYPE);
            $game->globals->set(Game::OREL_ID, $this->Id);

            if ($event->playerId == $this->ControllerId)
            {
                $game->notify->all("message", '${card_inject_code}: ${player_name} initiated the pressure and will succeed even if tied.', [
                    'card_inject_code' => $this->getInjectCode(),
                    'player_name' => $game->getPlayerNameById($this->ControllerId),
                ]);
                $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::OREL_WIN_TIES_PRESSURE_TYPE);
            }
        }
    }
}
