<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01029;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAmARiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01029 extends Risk implements IHasActions, IAmARiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('The Pressure is On');
        $this->Image = 'img/cards/7s5s/029.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 29;

        $this->Faction = 'Vodacce';

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 0;

        $this->WealthCost = 1;

        $this->Traits = [
            'Demoralize',
            'Duress',
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01029(),
        ];
    }
}