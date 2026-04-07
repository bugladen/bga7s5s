<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01034;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01034 extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Wrath of the Don');
        $this->Image = '01034.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 34;

        $this->initializeFaction('Vodacce');

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->WealthCost = 0;

        $this->Traits = [
            clienttranslate('Demoralize'),
            clienttranslate('Duress'),
            clienttranslate('Zeal'),
        ];

        $this->Text = clienttranslate("<p><b>City Action:</b> Wound your performer • Target opposing en garde character may engage. If they do not, en garde your performer.</p>");
        
        $this->resetCard();
        
        $this->Actions = [
            new Action_01034(),
        ];

    }
}