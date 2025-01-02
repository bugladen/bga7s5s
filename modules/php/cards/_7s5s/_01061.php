<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01061 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Well-Equipped";
        $this->Image = "img/cards/7s5s/061.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = 'Eisen';
        
        $this->WealthCost = 1;
        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 1;

        $this->Traits = [
            'Flourish',
            'Prepared',
            'Drexel',
        ];
    }
}