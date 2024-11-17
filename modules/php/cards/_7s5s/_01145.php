<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _01145 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Inspire Generosity";
        $this->Image = "img/cards/7s5s/145.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 145;

        $this->Faction = "";
        $this->Initiative = 15;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Bureaucracy", 
            "Camaraderie",
        ];
    }
}