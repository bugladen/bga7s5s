<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\core;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _01150 extends Scheme
{
    public function __construct()
    {
        $this->Name = "Parley Gone Wrong";
        $this->Image = "img/cards/7s5s/150.jpg";
        $this->ExpansionName = "Core";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 150;

        $this->Initiative = 55;
        $this->PanacheModifier = 1;

        $this->Traits = [
            "Fued", 
            "Provocation",
        ];
    }
}