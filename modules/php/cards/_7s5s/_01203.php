<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;

class _01203 extends CityCharacter
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Leja Juska";
        $this->Image = "img/cards/7s5s/203.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 203;

        $this->Title = 'Whispered Shade';

        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 1;

        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;

        $this->WealthCost = 5;
        $this->CityCardNumber = 27;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Duelist',
            'Sarmatian',
        ];
    }
}