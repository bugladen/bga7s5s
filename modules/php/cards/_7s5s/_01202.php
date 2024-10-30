<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;

class _01202 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Object of Wonder';
        $this->Image = "img/cards/7s5s/202.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 202;
        
        $this->CityCardNumber = 26;
        $this->WealthCost = 2;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 1;
        $this->InfluenceModifier = 1;

        $this->Traits = [
            'Artifact',
            'Syrneth',
            'Unique',
        ];
    }
}