<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _01152 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Until Morale Improves";
        $this->Image = "img/cards/7s5s/152.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 152;

        $this->Faction = "";
        $this->Initiative = 30;
        $this->PanacheModifier = -2;

        $this->Traits = [
            "Ad Hoc", 
            "Demoralize",
        ];
    }
}