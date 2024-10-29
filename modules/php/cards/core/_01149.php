<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\core;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _01149 extends Scheme
{
    public function __construct()
    {
        $this->Name = "Midnight Shipment";
        $this->Image = "img/cards/7s5s/149.jpg";
        $this->ExpansionName = "Core";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 149;

        $this->Initiative = 80;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Logistics", 
            "Market",
        ];
    }
}