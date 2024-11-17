<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _01143 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Contempt and Hatred";
        $this->Image = "img/cards/7s5s/143.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 143;

        $this->Faction = "";
        $this->Initiative = 43;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Demoralize", 
            "Duress",
        ];
    }
}