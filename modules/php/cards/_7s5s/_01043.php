<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01043 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Uwe Zimmerman";
        $this->Image = "img/cards/7s5s/043.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 43;

        $this->Faction = "Eisen";
        $this->Title = "The Unbroken Will";
        $this->Resolve = 5;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->resetModifiedCharacterStats();
        
        $this->Traits = [
            "Hunter",
            "Eisen",
        ];
    }

}