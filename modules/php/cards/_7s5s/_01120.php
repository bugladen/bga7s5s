<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01120 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Pavel Ivanov";
        $this->Image = "img/cards/7s5s/120.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 120;

        $this->Faction = "Usurra";
        $this->Title = "Resolute Scribe";
        $this->Resolve = 3;
        $this->Combat = 0;
        $this->Finesse = 2;
        $this->Influence = 3;

        $this->resetModifiedCharacterStats();
        
        $this->Traits = [
            "Academic",
            "Usurra",
        ];
    }

}