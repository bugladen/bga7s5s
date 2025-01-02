<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;

class _01188 extends CityCharacter
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Vladislav Novikoff';
        $this->Image = "img/cards/7s5s/188.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 188;
        
        $this->Title = 'Gentle Giant';

        $this->Resolve = 5;
        $this->Combat = 0;
        $this->Finesse = 0;
        $this->Influence = -1;

        $this->resetModifiedCharacterStats();

        $this->WealthCost = 4;
        $this->CityCardNumber = 12;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Usurra',
        ];
    }
}