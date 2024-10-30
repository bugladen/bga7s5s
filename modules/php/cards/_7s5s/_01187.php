<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;


class _01187 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Smuggled Item';
        $this->Image = "img/cards/7s5s/187.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 187;
        
        $this->CityCardNumber = 11;
        $this->WealthCost = 1;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 1;

        $this->Traits = [
            'Artifact',
        ];
    }
}