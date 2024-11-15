<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;

class _01177 extends CityEventCard
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Penya Shows The Way";
        $this->Image = "img/cards/7s5s/177.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 177;

        $this->CityCardNumber = 1;
    }
}