<?php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;

class _01195 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Eager Blade';
        $this->Image = "img/cards/7s5s/195.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 195;
        
        $this->CityCardNumber = 19;
        $this->WealthCost = 1;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 1;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Traits = [
            'Corruption',
            'Weapon',
            'Melee',
            'Sword',
            'Unique',
        ];
    }
}