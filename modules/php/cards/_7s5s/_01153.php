<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;

class _01153 extends FactionAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Breastplate";
        $this->Image = "img/cards/7s5s/153.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 1;
        $this->Riposte = 0;
        $this->Parry = 3;
        $this->Thrust = 1;

        $this->Traits = [
            'Armor',
        ];
    }
}