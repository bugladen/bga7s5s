<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01036 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Daniella Dietrich";
        $this->Image = "img/cards/7s5s/036.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 36;

        $this->Faction = "Eisen";
        $this->Title = "Rueful Confidante";
        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 1;

        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;
        
        $this->Traits = [
            "Sorcerer",
            "Strega",
            "Vodacce",
        ];
    }

}