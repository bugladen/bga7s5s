<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01135 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Mireli's Revision";
        $this->Image = "img/cards/7s5s/135.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = "Usurra";

        $this->WealthCost = 1;
        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 1;

        $this->Traits = [
            'Flourish',
            'Mireli',
        ];
    }

}