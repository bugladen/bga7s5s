<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _01045 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "The Song of Eisen";
        $this->Image = "img/cards/7s5s/045.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 45;

        $this->Faction = "Eisen";
        $this->Initiative = 67;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Bargain", 
            "Prepared",
        ];
    }
}