<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01132;

class _01132 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Matushka's Command");
        $this->Image = "01132.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 132;
        $this->initializeFaction("Ussura");

        $this->WealthCost = 1;
        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->Traits = [
            clienttranslate('Sorcery'),
            clienttranslate('Dar Matushki'),
        ];

        $this->Text = clienttranslate("<p>Sorcerer Action: Move all engaged characters at your performer's location Home. Then, engage all of the remaining characters there.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01132(),
        ];
    }

}