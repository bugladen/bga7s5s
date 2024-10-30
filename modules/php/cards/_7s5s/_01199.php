<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;

class _01199 extends CityCharacter
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Takama Siad";
        $this->Image = "img/cards/7s5s/199.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 199;

        $this->Title = 'Gilded Doctor';

        $this->Resolve = 3;
        $this->Combat = 0;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;

        $this->WealthCost = 3;
        $this->CityCardNumber = 23;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Academic',
            'Surgeon',
            'Maghreb',
        ];
    }
}