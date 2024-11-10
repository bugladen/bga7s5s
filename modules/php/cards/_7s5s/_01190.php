<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;

class _01190 extends CityCharacter
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Sigurd Ulfsen';
        $this->Image = "img/cards/7s5s/190.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 190;
        
        $this->Title = 'Grizzled Deathseeker';

        $this->Resolve = 5;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = -1;

        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;

        $this->WealthCost = 4;
        $this->CityCardNumber = 14;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Pirate',
            'Vesten',
        ];
    }
}