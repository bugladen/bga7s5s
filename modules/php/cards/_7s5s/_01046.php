<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;

class _01046 extends FactionAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Dark Gift";
        $this->Image = "img/cards/7s5s/046.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = 'Eisen';
        
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 1;
        $this->Riposte = 3;
        $this->Parry = 0;
        $this->Thrust = 0;

        $this->Traits = [
            'Sorcery',
            'Corruption',
            'Unique',
        ];
    }

}