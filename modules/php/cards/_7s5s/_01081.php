<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01081;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01081 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Gallant Deeds");
        $this->Image = "01081.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction("Montaigne");
        
        $this->WealthCost = 1;
        $this->Riposte = 1;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->Traits = [
            'Heroic',
            'Honor',
        ];

        $this->Text = clienttranslate("<p>City Action: Target an opposing engaged character • That character and your performer both en garde.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01081(),
        ];
    }
}