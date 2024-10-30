<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;

class _01200 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Crystal Eye';
        $this->Image = "img/cards/7s5s/200.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 200;
        
        $this->CityCardNumber = 24;
        $this->WealthCost = 1;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 1;

        $this->Traits = [
            'Artifact',
            'Syrneth',
            'Unique',
        ];
    }
}