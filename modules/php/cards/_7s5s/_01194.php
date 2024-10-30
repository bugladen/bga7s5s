<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;

class _01194 extends CityCharacter
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Adelheide Schmidt';
        $this->Image = "img/cards/7s5s/194.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 194;
        
        $this->Title = 'First to Swing';

        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 0;

        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;

        $this->WealthCost = 4;
        $this->CityCardNumber = 18;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Eisen',
        ];
    }
}