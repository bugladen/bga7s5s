<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01122 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Torsten Vakt";
        $this->Image = "img/cards/7s5s/122.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 122;

        $this->Faction = "Usurra";
        $this->Title = "Incorrigible Drunk";
        $this->Resolve = 6;
        $this->Combat = 3;
        $this->Finesse = 1;
        $this->Influence = 2;

        $this->resetModifiedCharacterStats();
        
        $this->Traits = [
            "Scoundrel",
            "Murskaaja",
            "Vesten",
        ];
    }

}