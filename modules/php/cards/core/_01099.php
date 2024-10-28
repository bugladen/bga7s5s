<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\core;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _01099 extends Scheme
{
    public function __construct()
    {
        $this->Name = "Shifting Blame";
        $this->Image = "img/cards/core/099.jpg";
        $this->ExpansionName = "Core";
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