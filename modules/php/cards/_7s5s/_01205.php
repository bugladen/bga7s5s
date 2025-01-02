<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;

class _01205 extends CityCharacter
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Giacinto";
        $this->Image = "img/cards/7s5s/205.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 205;

        $this->Title = 'Dogged Kidnapper';

        $this->Resolve = 3;
        $this->Combat = 0;
        $this->Finesse = 1;
        $this->Influence = 2;

        $this->resetModifiedCharacterStats();

        $this->WealthCost = 5;
        $this->CityCardNumber = 29;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Academic',
            'Diplomat',
            'Scoundrel',
            'Castille',
        ];
    }
}