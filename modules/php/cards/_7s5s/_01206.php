<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;

class _01206 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Captains Coat Pistol";
        $this->Image = "img/cards/7s5s/206.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 206;
        
        $this->CityCardNumber = 30;
        $this->WealthCost = 3;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Traits = [
            'Attire',
            'Coat',
        ];
    }
}
