<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;

class _01186 extends CityCharacter implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Maryam Benu Pleroma";
        $this->Image = "img/cards/7s5s/186.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 186;

        $this->Title = 'Impervious Champion';

        $this->Resolve = 5;
        $this->Combat = 4;
        $this->Finesse = 3;
        $this->Influence = -1;

        $this->resetModifiedCharacterStats();

        $this->WealthCost = 6;
        $this->CityCardNumber = 10;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Duelist',
            'Weapons Master',
            'Ashur',
        ];
    }

    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();
        $this->addTechniqueProperties($properties);

        return $properties;
    }
}