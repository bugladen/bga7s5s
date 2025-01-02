<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01067a extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Henri Michelet";
        $this->Image = "img/cards/7s5s/067a.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 67;

        $this->Faction = "Montaigne";
        $this->Title = "Maestro Machinist";
        $this->Resolve = 4;
        $this->Combat = 3;
        $this->Finesse = 3;
        $this->Influence = 1;

        $this->resetModifiedCharacterStats();
        
        $this->Traits = [
            "Duelist",
            "Musketeer",
            "Montaigne",
        ];
    }

}