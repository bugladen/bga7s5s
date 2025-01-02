<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01130 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Indomitable Will";
        $this->Image = "img/cards/7s5s/130.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = "Usurra";

        $this->WealthCost = 0;
        $this->Riposte = 1;
        $this->Parry = 2;
        $this->Thrust = 0;

        $this->Traits = [
            'Immovable',
            'Provocation',
        ];
    }

}