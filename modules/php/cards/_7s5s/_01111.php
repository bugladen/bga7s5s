<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01111 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Research";
        $this->Image = "img/cards/7s5s/111.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->Traits = [
            'Discovery',
            'Scholarship',
        ];
    }
}