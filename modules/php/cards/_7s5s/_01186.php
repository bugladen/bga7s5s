<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;

class _01186 extends CityCharacter
{
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
        $this->Influence = 0;

        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;

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
}