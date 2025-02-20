<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01034 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Wrath of the Don';
        $this->Image = 'img/cards/7s5s/034.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 34;

        $this->Faction = 'Vodacce';

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->WealthCost = 0;

        $this->Traits = [
            'Demoralize',
            'Duress',
            'Zeal',
        ];
    }
}