<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01024 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Bravos';
        $this->Image = 'img/cards/7s5s/024.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 24;

        $this->Faction = 'Vodacce';

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 3;

        $this->WealthCost = 1;

        $this->Traits = [
            'Conscription',
            'Gang',
        ];
    }
}