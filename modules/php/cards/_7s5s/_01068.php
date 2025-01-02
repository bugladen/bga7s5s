<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01068 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Léontine Giroux";
        $this->Image = "img/cards/7s5s/068.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 68;

        $this->Faction = "Montaigne";
        $this->Title = "Lithe Lioness";
        $this->Resolve = 5;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->resetModifiedCharacterStats();
        
        $this->Traits = [
            "Duelist",
            "Musketeer",
            "Sorcerer",
            "Montaigne",
        ];
    }

}