<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Brute;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01017 extends Brute implements IHasActions
{
    use ActionTrait;
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Alcee');
        $this->Image = 'img/cards/7s5s/017.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 17;

        $this->Title = 'Hoodlum';
        $this->Faction = 'Vodacce';

        $this->Resolve = 2;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 1;

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
            new Action_01017(),
        ];

    }
}