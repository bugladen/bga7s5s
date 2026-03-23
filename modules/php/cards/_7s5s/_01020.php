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
        $this->Image = '01020.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 20;

        $this->Title = 'Lummox';
        $this->initializeFaction('Vodacce');

        $this->Resolve = 2;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->Riposte = 4;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->WealthCost = 2;

        $this->Traits = [
            'Red Hand',
            'Thug',
            'Vodacce',
            'Unique',
            'Brute',
        ];

        $this->Text = clienttranslate("<p>Brute (Brutes do not count against your Crew Cap, go to the discard pile when destroyed, and are discarded from play at the end of the day.)</p><p>City Action: Destroy Dante • Move target character to that location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01020(),
        ];

    }
}