<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01102 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Unfortunate";
        $this->Image = "img/cards/7s5s/102.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->WealthCost = 0;
        $this->Riposte = 1;
        $this->Parry = 0;
        $this->Thrust = 3;

        $this->Traits = [
            'Hubris',
        ];
    }
}

