<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01038 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Otto Streit";
        $this->Image = "img/cards/7s5s/038.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 38;

        $this->Faction = "Eisen";
        $this->Title = "Industrous Ironmonger";
        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;
        
        $this->Traits = [
            "Academic",
            "Eisen",
        ];
    }

}