<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01160 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Bleed Out';
        $this->Image = 'img/cards/7s5s/160.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 160;

        $this->Riposte = 1;
        $this->Parry = 0;
        $this->Thrust = 3;

        $this->WealthCost = 1;

        $this->Traits = [
            'Villainous',
        ];
    }
}