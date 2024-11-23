<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;

class _01101 extends FactionAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Gallegos Blade";
        $this->Image = "img/cards/7s5s/101.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->Parry = 1;
        $this->Thrust = 4;

        $this->Traits = [
            'Weapon',
            'Melee',
            'Sword',
            'Aldana',
        ];
    }
}