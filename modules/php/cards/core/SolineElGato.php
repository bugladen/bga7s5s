<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\core;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;

class SolineElGate extends Leader
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Soline El Gato";
        $this->Image = "img/cards/core/089.jpg";
        $this->Expansion = "Core";
        $this->CardNumber = 89;

        $this->Faction = "Castillo";
        $this->Titles[] = "Prince of Theives";
        $this->Resolve = 7;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 2;
        $this->CrewCap = 6;
        $this->Panache = 6;

        $this->Traits = [
            "Leader",
            "Pirate",
            "Scoundrel",
            "Castille",
        ];
    }

}