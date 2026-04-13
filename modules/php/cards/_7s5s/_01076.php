<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01076;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01076 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Blood Mark");
        $this->Image = "01076.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 76;
        $this->initializeFaction('Montaigne');
        
        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->Traits = [
            clienttranslate('Sorcery'),
            clienttranslate('Porte'),
        ];

        $this->Text = clienttranslate("<p><b>Sorcerer Action:</b> Move your performer to any City location. Then, you may wound them. If you do, move another of your characters from the first location to this one.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01076(),
        ];
    }
}

