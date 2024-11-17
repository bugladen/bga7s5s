<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01124 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Ved'Ma";
        $this->Image = "img/cards/7s5s/124.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 124;

        $this->Faction = "Usurra";
        $this->Title = "Ancient Witch";
        $this->Resolve = 3;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 3;

        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;
        
        $this->Traits = [
            "Sorcerer",
            "Usurra",
        ];
    }

}