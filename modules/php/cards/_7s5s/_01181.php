<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;

class _01181 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Sorte Deck';
        $this->Image = "img/cards/7s5s/181.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 181;
        
        $this->CityCardNumber = 5;
        $this->WealthCost = 1;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Traits = [
            'Sorte',
            'Trinket',
        ];
    }
}

