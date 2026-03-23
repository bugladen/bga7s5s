<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01167;

class _01167 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Liberating Goods");
        $this->Image = "01167.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->Traits = [
            'Crime',
            'Theft',
        ];

        $this->Text = "<p>City Action: Take control of target non-Unique attachment in an opponent's discard pile. Equip it to your performer, paying all costs.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_01167(),
        ];
    }
}
