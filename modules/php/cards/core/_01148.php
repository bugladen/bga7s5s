<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\core;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _01148 extends Scheme
{
    public function __construct()
    {
        $this->Name = "Marooned";
        $this->Image = "img/cards/7s5s/148.jpg";
        $this->ExpansionName = "Core";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 148;

        $this->Initiative = 22;
        $this->PanacheModifier = 1;

        $this->Traits = [
            "Betrayal", 
            "Solitary",
        ];
    }
}