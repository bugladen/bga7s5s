<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01171 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Paid Off";
        $this->Image = "img/cards/7s5s/171.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->WealthCost = 2;
        $this->Riposte = 1;
        $this->Parry = 0;
        $this->Thrust = 2;

        $this->Traits = [
            'Cunning',
            'Villainous',
        ];
    }
}