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

        $this->Name = clienttranslate('Angelo');
        $this->Image = '01018.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 18;

        $this->Title = clienttranslate('Goon');
        $this->initializeFaction('Vodacce');

        $this->Resolve = 2;
        $this->Combat = 1;
        $this->Finesse = 1;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->WealthCost = 0;

        $this->Traits = [
            clienttranslate('Red Hand'),
            clienttranslate('Thug'),
            clienttranslate('Vodacce'),
            clienttranslate('Unique'),
            clienttranslate('Brute'),
        ];

        $this->Text = clienttranslate("<p>Brute (Brutes do not count against your Crew Cap, go to the discard pile when destroyed, and are discarded from play at the end of the day.)</p><p><b>City Action:</b> Destroy Angelo • Draw a card.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01018(),
        ];
    }
}