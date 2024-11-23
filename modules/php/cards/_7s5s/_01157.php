<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;

class _01157 extends FactionAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Throwing Knife";
        $this->Image = "img/cards/7s5s/157.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->Traits = [
            'Weapon',
            'Ranged',
            'Knife',
        ];
    }
}