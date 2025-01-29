<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_01039;

class _01039 extends Character implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Philip Hase";
        $this->Image = "img/cards/7s5s/039.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 39;

        $this->Faction = "Eisen";
        $this->Title = "Grim Trapper";
        $this->Resolve = 5;
        $this->Combat = 3;
        $this->Finesse = 1;
        $this->Influence = 2;

        $this->resetModifiedCharacterStats();
        
        $this->Traits = [
            "Academic",
            "Hunter",
            "Eisen",
        ];

        $this->Techniques = [
            new Technique_01039(),
        ];
    }

    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();
        $this->addTechniqueProperties($properties);

        return $properties;
    }
}