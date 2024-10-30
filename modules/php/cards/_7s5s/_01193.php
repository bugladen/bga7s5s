<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;

class _01193 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Burnished Cuirass';
        $this->Image = "img/cards/7s5s/193.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 193;
        
        $this->CityCardNumber = 17;
        $this->WealthCost = 1;

        $this->ResolveModifier = 1;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Traits = [
            'Armor',
        ];
    }
}