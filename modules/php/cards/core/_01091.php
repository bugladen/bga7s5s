<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\core;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01091 extends Character
{
    public function __construct()
    {
        $this->Name = "'Madre' Dolores";
        $this->Image = "img/cards/core/091.jpg";
        $this->ExpansionName = "Core";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 91;

        $this->Faction = "Castille";
        $this->Title = "Cat Lady of Castille";
        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 2;        
        $this->Influence = 3;

        $this->Traits = [
            "Academic",
            "Castille",
        ];
    }
}