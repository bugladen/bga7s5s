<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01103 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Adaptable";
        $this->Image = "img/cards/7s5s/103.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = "Castille";
        
        $this->WealthCost = 1;
        $this->Riposte = 0;
        $this->Parry = 0;
        $this->Thrust = 0;

        $this->Traits = [
            'Flourish',
            'Virtue',
            'Unique',
        ];
    }
}