<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01019;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Brute;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01019 extends Brute implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Buratino');
        $this->Image = '01019.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 19;

        $this->Title = clienttranslate('Lout');
        $this->initializeFaction('Vodacce');

        $this->Resolve = 2;
        $this->Combat = 3;
        $this->Finesse = 1;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->Riposte = 1;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 2;

        $this->WealthCost = 1;

        $this->Traits = [
            clienttranslate('Red Hand'),
            clienttranslate('Thug'),
            clienttranslate('Vodacce'),
            clienttranslate('Unique'),
            clienttranslate('Brute'),
        ];

        $this->Text = clienttranslate("<p>Brute (Brutes do not count against your Crew Cap, go to the discard pile when destroyed, and are discarded from play at the end of the day.)</p><p>City Action: Destroy Buratino • Wound target character at that location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01019(),
        ];

    }
}