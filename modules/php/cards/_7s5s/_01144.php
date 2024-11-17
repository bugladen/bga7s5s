<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _01144 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Filling The Ranks";
        $this->Image = "img/cards/7s5s/144.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 144;

        $this->Faction = "";
        $this->Initiative = 50;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Bargain", 
            "Conscription",
        ];
    }
}