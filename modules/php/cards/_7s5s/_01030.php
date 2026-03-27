<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01030;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01030 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Pull the Strand');
        $this->Image = '01030.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 30;

        $this->initializeFaction('Vodacce');

        $this->Riposte = 1;
        $this->Parry = 2;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->WealthCost = 0;

        $this->Traits = [
            clienttranslate('Sorcery'),
            clienttranslate('Sorte'),
            clienttranslate('Unique'),
        ];

        $this->Text = clienttranslate("<p>Sorcerer Strega Action: Engage your opposed performer • Pressure their location with [Inf]. Target opposing character adds to your total instead. If successful, claim the location.</p>");
 
        $this->resetCard();

        $this->Actions = [
            new Action_01030(),
        ];
   }
}