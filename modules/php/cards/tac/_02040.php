<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02040;

class _02040 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Siesta');
        $this->Image = '02040.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 40;

        $this->initializeFaction('Castille');

        $this->Riposte = 1;
        $this->Parry = 2;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->WealthCost = 0;

        $this->Traits = [
            clienttranslate('Rest'),
            clienttranslate('Recuperation'),
        ];

        $this->Text = clienttranslate("<b>City Action:</b> Move your performer <b>Home</b> engaged. Then, they heal a wound.");

        $this->resetCard();

        $this->Actions = [
            new Action_02040(),
        ];
    }
}