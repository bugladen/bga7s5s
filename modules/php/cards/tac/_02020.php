<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02020;

class _02020 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Yield");
        $this->Image = "02020.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 20;

        $this->initializeFaction("Eisen");

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 4;

        $this->WealthCost = 0;

        $this->Traits = [
            "Eisenfaust",
            "Demoralize",
            "Provocation",
        ];

        $this->Text = clienttranslate("<p><b>City Action:</b> Engage a <b>Melee Weapon</b> or <b>Eisenfaust</b> attachment equipped to your performer and target an opposing non-<b>Leader</b> • They may engage. If they do not, wound them.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_02020(),
        ];
    }
}