<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;

class _01184 extends CityCharacter
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Claude de la Roche";
        $this->Image = "img/cards/7s5s/184.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 184;

        $this->Title = 'Pompous Sleaze';

        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 1;
        $this->Influence = 2;

        $this->resetModifiedCharacterStats();

        $this->WealthCost = 6;        
        $this->CityCardNumber = 8;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Diplomat',
            'Montaigne',
        ];
    }
}