<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01141;

class _01141 extends Risk implements IHasActions
{
    use ActionTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Strong Hands");
        $this->Image = "01141.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 141;
        $this->initializeFaction("Ussura");

        $this->WealthCost = 1;
        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 4;

        $this->Traits = [
            clienttranslate('Brawl'),
            clienttranslate('Kulachniy Boi'),
        ];

        $this->Text = clienttranslate("<p><b>City Action:</b> Pressure your performer's location with [Combat]. If successful, claim it.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01141(),
        ];
    }
}