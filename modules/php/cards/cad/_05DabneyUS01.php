<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05DabneyUS01;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques\Technique_05DabneyUS01;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;

class _05DabneyUS01 extends Leader implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = "Valeri Mikhailov";
        $this->Title = "A Lonely Bad Boy";
        $this->Image = "05DabneyUS01_0.4.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 1;

        $this->initializeFaction("Ussura");

        $this->Resolve = 7;
        $this->Combat = 2;
        $this->Finesse = 4;
        $this->Influence = 1;

        $this->CrewCap = 4;
        $this->Panache = 7;

        $this->Traits = [
            "Leader",
            "Villain",
            "Duelist",
            "Scion",
            "Ussura"
        ];

        $this->Text = "<p><i>Covert</i> - During pressures at Valeri's location, add +2 to your total.
<br>(<i>Covert Abilities may only be used at uncontrolled locations.</i>)</p>
<p><b>City Action:</b> Move Valeri to an adjacent <b>City</b> location. He issues a [Combat] challenge to target opposing character.</p>
<p><b>Technique:</b> +1[Parry], +1[Riposte], or gain Lethal</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_05DabneyUS01(),
        ];

        // Character already implements IHasTechniques / TechniqueTrait
        $this->Techniques = [
            new Technique_05DabneyUS01(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY: Covert pressure aura — Solomonia/Loyal shape (EventPressureOccuring → PRESSURE_TYPE flag).
        // Gate on uncontrolled City location: Covert parenthetical. +2 is any pressure type, not Influence-only.
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

            $cityLocation = $event->theah->getCityLocation($this->Location);
            if ($cityLocation === null || $cityLocation->isControlled())
            {
                return;
            }

            $game = $event->theah->game;
            $game->notify->all("message", '${card_inject_code} (Covert) will add +2 to ${player_name}\'s pressure total at this uncontrolled location.', [
                'card_inject_code' => $this->getInjectCode(),
                'player_name' => $game->getPlayerNameById($this->ControllerId),
            ]);

            $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::VALERI_PRESSURE_TYPE);
            $game->globals->set(Game::VALERI_ID, $this->Id);
        }
    }
}
