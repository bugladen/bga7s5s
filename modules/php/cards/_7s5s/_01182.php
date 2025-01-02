<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;

class _01182 extends CityCharacter
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Eko Sorridi";
        $this->Image = "img/cards/7s5s/182.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 182;

        $this->Title = 'Short-Tempered Gambler';

        $this->Resolve = 3;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->resetModifiedCharacterStats();

        $this->WealthCost = 4;        
        $this->CityCardNumber = 6;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Pirate',
            'Maghreb',
        ];
    }
}

