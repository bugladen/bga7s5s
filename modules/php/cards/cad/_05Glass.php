<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Glass;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\maneuvers\Maneuver_05Glass;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _05Glass extends Risk implements IHasActions, IHasManeuvers
{
    use ActionTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Unseat";
        $this->Image = "05Glass.v4.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 12;

        $this->initializeFaction("Ussura");

        $this->WealthCost = 1;
        $this->Riposte = 2;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 1;

        $this->Traits = [
            "Flourish",
            "Demoralize",
            "Overthrow",
        ];

        $this->Text = "<p><b>City Action:</b> Pressure your performer’s location with [Finesse]. If successful, this location becomes uncontrolled.</p>
<p><b>Maneuver:</b> If the adversary has more [Influence] than your participant • Wound them.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_05Glass(),
        ];

        $this->Maneuvers = [
            new Maneuver_05Glass(),
        ];
    }
}
