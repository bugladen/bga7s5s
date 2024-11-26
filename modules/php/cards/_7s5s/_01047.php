<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;

class _01047 extends FactionAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Kaspar's Panzerhand";
        $this->Image = "img/cards/7s5s/047.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->ResolveModifier = 1;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 2;
        $this->Riposte = 0;
        $this->Parry = 4;
        $this->Thrust = 1;

        $this->Traits = [
            'Armor',
            'Eisenfaust',
            'Unique',
        ];
    }

}