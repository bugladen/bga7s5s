<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01008 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Cesca Del Rosso";
        $this->Image = "img/cards/7s5s/008.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 8;

        $this->Faction = "Vodacce";
        $this->Title = "Sadistic Weaver";
        $this->Resolve = 5;
        $this->Combat = 1;
        $this->Finesse = 1;
        $this->Influence = 2;

        $this->resetModifiedCharacterStats();
        
        $this->Traits = [
            "Sorcerer",
            "Strega",
            "Red Hand",
            "Vodacce",
        ];
    }

}