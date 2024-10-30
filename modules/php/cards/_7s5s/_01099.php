<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _01099 extends Scheme
{
    public function __construct()
    {
        $this->Name = "Shifting Blame";
        $this->Image = "img/cards/7s5s/099.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 99;

        $this->Faction = "Castille";
        $this->Initiative = 10;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Cunning", 
            "Rumor",
        ];
    }
}