<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03032;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03032 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Bloody Entrance");
        $this->Image = '03032.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 32;

        $this->initializeFaction("Montaigne");

        $this->WealthCost = 1;

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate("Sorcery"),
            clienttranslate("Porté")
        ];

        $this->Text = clienttranslate("<b>Sorcerer City Action:</b> Wound your performer • Move them to any location, then they may perform another action. <i>(It must be performed and they must be the performer of the action)</i>");

        $this->resetCard();

        $this->Actions = [
            new Action_03032(),
        ];
    }
}
