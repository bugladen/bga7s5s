<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;

class _01191 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Duckfoot Pistol';
        $this->Image = "img/cards/7s5s/191.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 191;
        
        $this->CityCardNumber = 15;
        $this->WealthCost = 1;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Traits = [
            'Weapon',
            'Ranged',
            'Pistol',
        ];
    }
}