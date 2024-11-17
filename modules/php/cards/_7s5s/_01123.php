<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01123 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Valeri Mikhailov";
        $this->Image = "img/cards/7s5s/123.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 123;

        $this->Faction = "Usurra";
        $this->Title = "Champion Narcissist";
        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 1;

        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;
        
        $this->Traits = [
            "Duelist",
            "Usurra",
        ];
    }

}