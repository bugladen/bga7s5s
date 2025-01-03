<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01028 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Pack Tactics';
        $this->Image = 'img/cards/7s5s/028.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 28;

        $this->Faction = 'Vodacce';

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 1;

        $this->WealthCost = 1;

        $this->Traits = [
            'Camaraderie',
            'Gang',
        ];
    }
}