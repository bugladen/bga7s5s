<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01013 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Vissenta Scarpa";
        $this->Image = "img/cards/7s5s/013.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 13;

        $this->Faction = "Vodacce";
        $this->Title = "Mouse Among Rats";
        $this->Resolve = 4;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->resetModifiedCharacterStats();
        
        $this->Traits = [
            "Hero",
            "Duelist",
            "Vodacce",
        ];
    }

}