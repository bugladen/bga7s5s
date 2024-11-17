<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01009 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Cirilo Naucriparos";
        $this->Image = "img/cards/7s5s/009.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 9;

        $this->Faction = "Vodacce";
        $this->Title = "Self-serving Serpent";
        $this->Resolve = 4;
        $this->Combat = 3;
        $this->Finesse = 1;
        $this->Influence = 2;

        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;
        
        $this->Traits = [
            "Red Hand",
            "Extortionist",
            "Numa",
        ];
    }

}