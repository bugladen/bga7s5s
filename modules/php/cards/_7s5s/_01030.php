<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01030 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Pull the Strand';
        $this->Image = 'img/cards/7s5s/030.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 30;

        $this->Faction = 'Vodacce';

        $this->Riposte = 1;
        $this->Parry = 2;
        $this->Thrust = 0;

        $this->WealthCost = 0;

        $this->Traits = [
            'Sorcery',
            'Sorte',
            'Unique',
        ];
    }
}