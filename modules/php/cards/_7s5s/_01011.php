<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01011 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Servo Scarpa";
        $this->Image = "img/cards/7s5s/011.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 11;

        $this->Faction = "Vodacce";
        $this->Title = "Haughty Heir";
        $this->Resolve = 5;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->resetModifiedCharacterStats();
        
        $this->Traits = [
            "Deulist",
            "Red Hand",
            "Vodacce",
        ];
    }

}