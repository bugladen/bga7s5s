<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _01149 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Midnight Shipment";
        $this->Image = "img/cards/7s5s/149.jpg";
        $this->ExpansionName = "_7s5s";
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