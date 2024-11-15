<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;

class _01189 extends CityEventCard
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Point of Opportunity';
        $this->Image = "img/cards/7s5s/189.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 189;
        
        $this->CityCardNumber = 13;

        $this->Traits = [
            'Duress',
            'Fortune',
        ];
    }
}