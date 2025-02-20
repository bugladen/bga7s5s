<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01176 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Triage";
        $this->Image = "img/cards/7s5s/176.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->WealthCost = 1;
        $this->Riposte = 0;
        $this->Parry = 0;
        $this->Thrust = 4;

        $this->Traits = [
            'Heroic',
        ];
    }
}