<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01138 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Ruzarushitel";
        $this->Image = "img/cards/7s5s/138.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = "Usurra";

        $this->WealthCost = 1;
        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 4;

        $this->Traits = [
            'Brawl',
            'Hunt',
        ];
    }

}