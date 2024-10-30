<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;

class _01198 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Guild Triskelion';
        $this->Image = "img/cards/7s5s/198.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 198;
        
        $this->CityCardNumber = 22;
        $this->WealthCost = 2;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 1;
        $this->InfluenceModifier = 0;

        $this->Traits = [
            'Bureaucracy',
            'Trinket',
        ];
    }
}