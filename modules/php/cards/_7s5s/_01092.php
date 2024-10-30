<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01092 extends Character
{
    public function __construct()
    {
        $this->Name = "Makepeace Botwighte";
        $this->Image = "img/cards/7s5s/092.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 92;

        $this->Faction = "Castille";
        $this->Title = "Gracious Cheat";
        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;

        $this->Traits = [
            "Diplomat",
            "Scoundrel",
            "Avalon",
        ];
    }
}