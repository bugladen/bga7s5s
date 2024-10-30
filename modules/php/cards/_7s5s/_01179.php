<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Event;

class _01179 extends Event
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Siren's Scream";
        $this->Image = "img/cards/7s5s/179.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 179;

        $this->CityCardNumber = 3;
    }
}