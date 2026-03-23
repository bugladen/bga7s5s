<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02018;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02018;

class _02018 extends Risk implements IHasActions, IHasManeuvers
{
    use ActionTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Divine Discipline");
        $this->Image = "02018.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 18;

        $this->initializeFaction("Eisen");

        $this->Riposte = 0;
        $this->DashedRiposte = true;

        $this->Parry = 1;
        $this->Thrust = 3;

        $this->WealthCost = 1;

        $this->Traits = [
            "Flourish",
            "Faith",
            "Penance",
        ];

        $this->Text = clienttranslate("<p><b>Zealot City Action:</b> Wound all characters at your performer's location.</p><p><b>Maneuver:</b> Wound your participant • +X[thrust] where X is the number of wounds on your participant.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_02018(),
        ];

        $this->Maneuvers = [
            new Maneuver_02018(),
        ];
    }
}