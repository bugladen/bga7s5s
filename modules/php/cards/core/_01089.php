<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\core;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;

class _01089 extends Leader
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Soline El Gato";
        $this->Image = "img/cards/core/089.jpg";
        $this->ExpansionName = "Core";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 89;

        $this->Faction = "Castillo";
        $this->Title = "Prince of Thieves";
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