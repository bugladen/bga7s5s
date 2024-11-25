<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01081 extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Gallant Deeds";
        $this->Image = "img/cards/7s5s/081.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->WealthCost = 1;
        $this->Riposte = 1;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->Traits = [
            'Heroic',
            'Honor',
        ];
    }
}