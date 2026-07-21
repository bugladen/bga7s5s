<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03071;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03071 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Leverage");
        $this->Image = '03071.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 71;

        $this->initializeFaction('Neutral');

        $this->WealthCost = 2;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 3;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate("Bureaucracy"),
            clienttranslate("Villainous")
        ];

        $this->Text = clienttranslate("<p>This card has -1 cost if your <b>Leader</b> is a <b>Villain</b> or <b>Pirate</b></p>
        <p><b>City Action:</b> If this location is controlled by an opponent • Engage an opposing character.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03071(),
        ];
    }
}
