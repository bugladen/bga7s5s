<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _01072 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Réputation Méritée";
        $this->Image = "img/cards/7s5s/072.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 72;

        $this->Faction = "Montaigne";
        $this->Initiative = 62;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Camaraderie", 
            "Honor",
        ];
    }
}