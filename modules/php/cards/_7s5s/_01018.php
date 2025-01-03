<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionCharacter;

class _01018 extends FactionCharacter
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Angelo';
        $this->Image = 'img/cards/7s5s/018.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 18;

        $this->Title = 'Goon';
        $this->Faction = 'Vodacce';

        $this->Resolve = 2;
        $this->Combat = 1;
        $this->Finesse = 1;
        $this->Influence = 0;

        $this->resetModifiedCharacterStats();

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 0;

        $this->WealthCost = 0;

        $this->Traits = [
            'Red Hand',
            'Thug',
            'Vodacce',
            'Unique',
            'Brute',
        ];
    }
}