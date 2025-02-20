<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;

class _01183 extends CityEventCard
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "So It Begins";
        $this->Image = "img/cards/7s5s/183.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 183;

        $this->CityCardNumber = 7;

        $this->Traits = [
            'Brawl',
            'Feud',
        ];
    }
}