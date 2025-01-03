<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01023 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Ambush';
        $this->Image = 'img/cards/7s5s/023.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 23;

        $this->Faction = 'Vodacce';

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->WealthCost = 1;

        $this->Traits = [
            'Brawl',
            'Gang',
        ];
    }
}