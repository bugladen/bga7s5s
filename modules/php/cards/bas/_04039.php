<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _04039 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Rapsodia');
        $this->Image = '04039.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 39;

        $this->initializeFaction("Castille");

        $this->WealthCost = 1;

        $this->Riposte = 0;
        $this->Parry = 1;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->Traits = [
            clienttranslate("Discovery"),
            clienttranslate("Performance"),
            clienttranslate("Faith")
        ];

        $this->Text = clienttranslate("<p>While your performer is an <b>Zealot</b>, <b>Academic</b>, or <b>Bard</b>, this card has -1 cost.</p>
<p><b>City Action:</b> Engage your performer and lose control of a <b>City</b> location • Claim your performer's location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04039(),
        ];
    }
}
