<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01162;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01162 extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Come Hither");
        $this->Image = "01162.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 162;
        
        $this->WealthCost = 2;
        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->Traits = [
            clienttranslate('Romance'),
            clienttranslate('Temptation'),
        ];

        $this->Text = clienttranslate("<p><b>Action:</b> Target a character • Move them to an adjacent City location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01162(),
        ];
    }

}