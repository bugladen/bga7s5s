<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01026;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAmARiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01026 extends Risk implements IHasActions, IAmARiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('For the Family');
        $this->Image = 'img/cards/7s5s/026.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 26;

        $this->Faction = 'Vodacce';

        $this->Riposte = 0;
        $this->Parry = 3;
        $this->Thrust = 1;

        $this->WealthCost = 0;

        $this->Traits = [
            'Glory',
            'Zeal',
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01026(),
        ];
    }
}