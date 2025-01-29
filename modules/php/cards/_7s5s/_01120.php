<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneParry;


class _01120 extends Character implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Pavel Ivanov";
        $this->Image = "img/cards/7s5s/120.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 120;

        $this->Faction = "Usurra";
        $this->Title = "Resolute Scribe";
        $this->Resolve = 3;
        $this->Combat = 0;
        $this->Finesse = 2;
        $this->Influence = 3;

        $this->resetModifiedCharacterStats();
        
        $this->Traits = [
            "Academic",
            "Usurra",
        ];

        $technique = new Technique_PlusOneParry();
        $technique->setId("Technique_01120");
        $technique->Name = "Pavel Ivanov: +1 Parry";
        $this->Techniques = [
            $technique,
        ];
    }

    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();
        $this->addTechniqueProperties($properties);

        return $properties;
    }
}