<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _01016 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Plans Within Plans";
        $this->Image = "img/cards/7s5s/016.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 16;

        $this->Faction = "Vodacce";
        $this->Initiative = 73;
        $this->PanacheModifier = -1;

        $this->Traits = [
            "Cunning", 
            "Gang",
        ];
    }
}