<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;

class _01180 extends CityCharacter
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Kaj Kousei";
        $this->Image = "img/cards/7s5s/180.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 180;

        $this->Title = 'The Thorn';

        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;

        $this->WealthCost = 4;        
        $this->CityCardNumber = 4;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Academic',
            "Explorer's Society",
            'Numa'
        ];
    }
}