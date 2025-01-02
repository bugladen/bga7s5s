<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;

class _01201 extends CityCharacter
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Ravenna Destine";
        $this->Image = "img/cards/7s5s/201.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 201;

        $this->Title = 'Doomsayer';

        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->resetModifiedCharacterStats();

        $this->WealthCost = 4;
        $this->CityCardNumber = 25;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Sorcerer',
            'Strega',
            'Vodacce',
        ];
    }
}