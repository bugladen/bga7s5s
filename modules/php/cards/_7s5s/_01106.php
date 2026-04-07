<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01106;

class _01106 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Improvising");
        $this->Image = "01106.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 106;
        $this->initializeFaction("Castille");
        
        $this->WealthCost = 0;
        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate('Ad Hoc'),
            clienttranslate('Savvy'),
        ];

        $this->Text = clienttranslate("<p><b>Action:</b> Play target risk from an opponent's discard pile, paying all costs. After it resolves, sink it. Send this card to The Locker. (Cards return to their owner's deck when sunk.)</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01106(),
        ];
    }
}