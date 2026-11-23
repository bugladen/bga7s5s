<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Keelhaula;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Keelhaulb;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _05Keelhaul extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Surprise Boarding";
        $this->Image = "05Keelhaul.v5.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 30;

        $this->initializeFaction("Castille");

        $this->WealthCost = 1;

        $this->Riposte = 1;
        $this->Parry = 0;
        $this->Thrust = 3;

        $this->Traits = [
            "Challenge",
            "Savvy",
            "Ambush",
            "Stealth",
        ];

        $this->Text = "<p><b>Covert Pirate Action:</b> Your performer issues a [Combat] challenge to target opposing character.</p>
<p><b>Covert Action:</b> If you aren't the first player • Your performer issues a [Finesse] challenge to target opposing character.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_05Keelhaula(),
            new Action_05Keelhaulb(),
        ];
    }
}
