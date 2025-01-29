<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_01123;


class _01123 extends Character implements IHasTechniques
{
    use TechniqueTrait;

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

        $this->resetModifiedCharacterStats();
        
        $this->Traits = [
            "Duelist",
            "Usurra",
        ];

        $this->Techniques = [
            new Technique_01123(),
        ];
    }

    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();
        $this->addTechniqueProperties($properties);

        return $properties;
    }
}