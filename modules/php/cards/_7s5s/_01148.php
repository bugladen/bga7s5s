<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _01148 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Marooned";
        $this->Image = "img/cards/7s5s/148.jpg";
        $this->ExpansionName = "_7s5s";
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