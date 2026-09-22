<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _04049 extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Point of Order');
        $this->Image = '04049.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 49;

        $this->initializeFaction("Ussura");

        $this->WealthCost = 1;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->Traits = [
            clienttranslate("Bureaucracy"),
            clienttranslate("Authority"),
            clienttranslate("Demoralize"),
        ];

        $this->Text = clienttranslate("<p><b>City Action:</b> Target an opposing character with lower [Influence] than your performer • They may engage. If they do not, they must move to an adjacent <b>City</b> location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04049(),
        ];
    }
}
