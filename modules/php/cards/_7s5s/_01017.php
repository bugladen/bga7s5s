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
        $this->Image = '01017.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 17;

        $this->Title = clienttranslate('Hoodlum');
        $this->initializeFaction('Vodacce');

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
            clienttranslate('Red Hand'),
            clienttranslate('Thug'),
            clienttranslate('Vodacce'),
            clienttranslate('Unique'),
            clienttranslate('Brute'),
        ];

        $this->Text = clienttranslate("<p>Brute (Brutes do not count against your Crew Cap, go to the discard pile when destroyed, and are discarded from play at the end of the day.)</p><p><b>City Action:</b> Destroy Alcee • Engage target character at that location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01017(),
        ];

    }
}