<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Refuge;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _05Refuge extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Scholarly Sanctuary";
        $this->Image = "05Refuge.v5.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 18;

        $this->initializeFaction("Ussura");

        $this->WealthCost = 1;

        $this->Riposte = 0;
        $this->Parry = 3;
        $this->Thrust = 1;

        $this->Traits = [
            "Scholarship",
            "Discovery"
        ];

        $this->Text = "<p>If your performer is equipped with an <b>Artifact</b>, this card has -1 cost.</p>
<p><b>Academic Action:</b> Move your performer <b>Home</b> or to a <b>City</b> location you control.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_05Refuge(),
        ];
    }
}
