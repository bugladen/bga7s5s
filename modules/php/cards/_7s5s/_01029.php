<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01029 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'The Pressure is On';
        $this->Image = 'img/cards/7s5s/029.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 29;

        $this->Faction = 'Vodacce';

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 0;

        $this->WealthCost = 1;

        $this->Traits = [
            'Demoralize',
            'Duress',
        ];
    }
}