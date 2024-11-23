<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01169 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Not Today";
        $this->Image = "img/cards/7s5s/169.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->Parry = 5;
        $this->Thrust = 0;

        $this->Traits = [
            'Ad Hoc',
        ];
    }
}