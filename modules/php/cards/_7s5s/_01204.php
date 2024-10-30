<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;

class _01204 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Syrneth Hand';
        $this->Image = "img/cards/7s5s/204.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 204;
        
        $this->CityCardNumber = 28;
        $this->WealthCost = 2;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 1;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Traits = [
            'Artifact',
            'Syrneth',
            'Unique',
        ];
    }
}

