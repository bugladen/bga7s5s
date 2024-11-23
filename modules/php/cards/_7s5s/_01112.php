<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01112 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Carnaval";
        $this->Image = "img/cards/7s5s/112.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->Parry = 3;
        $this->Thrust = 2;

        $this->Traits = [
            'Revelry',
        ];
    }
}