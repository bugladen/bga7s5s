<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _01015 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "The Great Game";
        $this->Image = "img/cards/7s5s/015.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 15;

        $this->Faction = "Vodacce";
        $this->Initiative = 60;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Bureaucracy", 
            "Zeal",
        ];
    }
}