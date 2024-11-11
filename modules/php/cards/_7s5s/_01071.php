<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _01071 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Épée Sanglante";
        $this->Image = "img/cards/7s5s/071.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 71;

        $this->Faction = "Montaigne";
        $this->Initiative = 26;
        $this->PanacheModifier = -1;

        $this->Traits = [
            "Challenge", 
            "Duty",
            "Glory",
        ];
    }
}