<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01088 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "You're Embarrassing Yourself";
        $this->Image = "img/cards/7s5s/088.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->WealthCost = 0;
        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 1;

        $this->Traits = [
            'Flourish',
            'Demoralize',
            'Valroux',
        ];
    }
}