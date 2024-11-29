<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01141 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Strong Hands";
        $this->Image = "img/cards/7s5s/141.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->WealthCost = 1;
        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 4;

        $this->Traits = [
            'Brawl',
            'Kulachniy Boi',
        ];
    }
}