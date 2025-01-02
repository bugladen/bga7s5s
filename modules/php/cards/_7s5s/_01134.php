<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01134 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Matushka's Sight";
        $this->Image = "img/cards/7s5s/134.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = "Usurra";

        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->Parry = 1;
        $this->Thrust = 4;

        $this->Traits = [
            'Sorcery',
            'Dar Matushki',
        ];
    }

}