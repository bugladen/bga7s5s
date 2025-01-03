<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01025 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Fate's Burden";
        $this->Image = 'img/cards/7s5s/025.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 25;

        $this->Faction = 'Vodacce';

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->WealthCost = 0;

        $this->Traits = [
            'Sorcery',
            'Sorte',
        ];
    }
}