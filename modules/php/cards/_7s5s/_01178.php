<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;

class _01178 extends CityCharacter  
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Carmella Vanessa Slavaggi';
        $this->Image = 'img/cards/7s5s/178.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 178;

        $this->Title = 'Lady V';

        $this->Resolve = 4;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;

        $this->WealthCost = 5;        
        $this->CityCardNumber = 2;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Duelist',
            'Vodacce',
        ];
    }
}