<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01026 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'For the Family';
        $this->Image = 'img/cards/7s5s/026.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 26;

        $this->Faction = 'Vodacce';

        $this->Riposte = 0;
        $this->Parry = 3;
        $this->Thrust = 1;

        $this->WealthCost = 0;

        $this->Traits = [
            'Glory',
            'Zeal',
        ];
    }
}