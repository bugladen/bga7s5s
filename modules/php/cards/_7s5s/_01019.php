<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionCharacter;

class _01019 extends FactionCharacter
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Buratino';
        $this->Image = 'img/cards/7s5s/019.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 19;

        $this->Title = 'Lout';
        $this->Faction = 'Vodacce';

        $this->Resolve = 2;
        $this->Combat = 3;
        $this->Finesse = 1;
        $this->Influence = 0;

        $this->resetModifiedCharacterStats();

        $this->Riposte = 1;
        $this->Parry = 0;
        $this->Thrust = 2;

        $this->WealthCost = 1;

        $this->Traits = [
            'Red Hand',
            'Thug',
            'Vodacce',
            'Unique',
            'Brute',
        ];
    }
}