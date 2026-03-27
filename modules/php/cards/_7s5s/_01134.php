<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01134;

class _01134 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Matushka's Sight");
        $this->Image = "01134.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction("Ussura");

        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 1;
        $this->Thrust = 4;

        $this->Traits = [
            clienttranslate('Sorcery'),
            clienttranslate('Dar Matushki'),
        ];

        $this->Text = clienttranslate("<p>Sorcerer Action: Look at the top five cards of any deck. You may discard any number of them up to your performer's [Influence]. Replace the rest in any order. You may engage your performer. If you do, draw a card.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01134(),
        ];
    }

}