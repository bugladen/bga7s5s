<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01069 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Maxime De Lafayette";
        $this->Image = "img/cards/7s5s/069.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 69;

        $this->Faction = "Montaigne";
        $this->Title = "Bloody Socialite";
        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 3;

        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;
        
        $this->Traits = [
            "Villain",
            "Sorcerer",
            "Montaigne",
        ];
    }

}