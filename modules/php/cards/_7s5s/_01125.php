<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _01125 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "The Boar's Guile";
        $this->Image = "img/cards/7s5s/125.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 125;

        $this->Faction = "Usurra";
        $this->Initiative = 40;
        $this->PanacheModifier = 1;

        $this->Traits = [
            "Cunning", 
            "Hunt",
        ];
    }
}