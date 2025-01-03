<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01174 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Shoddy Craftsmanship';
        $this->Image = 'img/cards/7s5s/174.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 174;

        $this->Riposte = 0;
        $this->Parry = 1;
        $this->Thrust = 3;

        $this->WealthCost = 1;

        $this->Traits = [
            'Sabotage',
        ];
    }
}