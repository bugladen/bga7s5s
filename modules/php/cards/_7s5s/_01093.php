<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_01093;

class _01093 extends Character implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Maya De La Rioja";
        $this->Image = "img/cards/7s5s/093.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 93;

        $this->Faction = "Castille";
        $this->Title = "Amoral Compass";
        $this->Resolve = 5;
        $this->Combat = 3;
        $this->Finesse = 2;       
        $this->Influence = 1;

        $this->resetModifiedCharacterStats();

        $this->Traits = [
            "Duelist",
            "Pirate",
            "Castille",
        ];

        $this->Techniques = [
            new Technique_01093(),
        ];
    }

    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();
        $this->addTechniqueProperties($properties);

        return $properties;
    }
}