<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _01147 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Let's Haggle";
        $this->Image = "img/cards/7s5s/147.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 147;

        $this->Initiative = 77;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Bargain", 
            "Market",
        ];
    }
}