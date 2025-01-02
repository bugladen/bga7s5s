<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;

class _01192 extends CityCharacter
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Gustavo';
        $this->Image = "img/cards/7s5s/192.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 192;
        
        $this->Title = 'Humble Powerbroker';

        $this->Resolve = 3;
        $this->Combat = 0;
        $this->Finesse = 3;
        $this->Influence = 2;

        $this->resetModifiedCharacterStats();

        $this->WealthCost = 4;
        $this->CityCardNumber = 16;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Academic',
            'Diplomat',
            'Villain',
            'Castille',
        ];
    }
}