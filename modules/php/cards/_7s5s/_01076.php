<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01076 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Blood Mark";
        $this->Image = "img/cards/7s5s/076.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = 'Montaigne';
        
        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 0;

        $this->Traits = [
            'Sorcery',
            'Porte',
        ];
    }
}

