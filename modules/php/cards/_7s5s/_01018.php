<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01018;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Brute;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01018 extends Brute implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Angelo';
        $this->Image = 'img/cards/7s5s/018.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 18;

        $this->Title = 'Goon';
        $this->initializeFaction('Vodacce');

        $this->Resolve = 2;
        $this->Combat = 1;
        $this->Finesse = 1;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 0;

        $this->WealthCost = 0;

        $this->Traits = [
            'Red Hand',
            'Thug',
            'Vodacce',
            'Unique',
            'Brute',
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01018(),
        ];
    }
}