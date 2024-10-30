<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _01098 extends Scheme
{
    public function __construct()
    {
        $this->Name = "The Cat's Embargo";
        $this->Image = "img/cards/7s5s/098.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 98;

        $this->Faction = "Castille";
        $this->Initiative = 75;
        $this->PanacheModifier = 1;

        $this->Traits = [
            "Logistics", 
            "Sabotage",
        ];
    }
}