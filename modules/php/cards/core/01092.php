<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\core;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01092 extends Character
{
    public function __construct()
    {
        $this->Name = "Makepeace Botwighte";
        $this->Image = "img/cards/core/092.jpg";
        $this->ExpansionName = "Core";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 92;

        $this->Faction = "Castille";
        $this->Title = "Gracious Cheat";
        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 2;        
        $this->Influence = 2;

        $this->Traits = [
            "Diplomat",
            "Scoundrel",
            "Avalon",
        ];
    }
}