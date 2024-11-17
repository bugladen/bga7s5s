<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _01044 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Armed and Marshaled";
        $this->Image = "img/cards/7s5s/044.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 44;

        $this->Faction = "Eisen";
        $this->Initiative = 37;
        $this->PanacheModifier = -1;

        $this->Traits = [
            "Duress", 
            "Logistics",
        ];
    }
}