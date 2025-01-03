<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;

class _01022 extends FactionAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Stiletto';
        $this->Image = 'img/cards/7s5s/022.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 22;

        $this->Faction = 'Vodacce';

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 1;
        $this->InfluenceModifier = 0;

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 2;

        $this->WealthCost = 1;

        $this->Traits = [
            'Weapon',
            'Melee',
            'Knife',
            'Ambrogia',
        ];
    }
}