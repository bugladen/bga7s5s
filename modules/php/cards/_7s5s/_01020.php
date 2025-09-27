<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01020;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Brute;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01020 extends Brute implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Dante');
        $this->Image = 'img/cards/7s5s/020.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 20;

        $this->Title = 'Lummox';
        $this->Faction = 'Vodacce';

        $this->Resolve = 2;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->Riposte = 4;
        $this->Parry = 0;
        $this->Thrust = 0;

        $this->WealthCost = 2;

        $this->Traits = [
            'Red Hand',
            'Thug',
            'Vodacce',
            'Unique',
            'Brute',
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01020(),
        ];

    }
}