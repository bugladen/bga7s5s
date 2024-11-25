<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01159 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Appealing to the People";
        $this->Image = "img/cards/7s5s/159.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->WealthCost = 2;
        $this->Riposte = 0;
        $this->Parry = 3;
        $this->Thrust = 1;

        $this->Traits = [
            'Beauracracy',
            'Heroic',
        ];
    }
}