<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02007;

class _02007 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Arson");
        $this->Image = "02007.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 7;

        $this->initializeFaction("Vodacce");

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->WealthCost = 0;

        $this->Traits = [
            "Crime",
            "Sabotage",
        ];

        $this->Text = "<p><b>Red Hand City Action:</b> Wound your performer • Remove a Renown and discard target available City card from your performer's location.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_02007(),
        ];
    }
}