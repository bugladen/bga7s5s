<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01162 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Come Hither";
        $this->Image = "img/cards/7s5s/162.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->WealthCost = 2;
        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->Traits = [
            'Romance',
            'Temptation',
        ];
    }

}