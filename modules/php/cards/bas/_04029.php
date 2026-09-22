<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04029;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _04029 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("A Fine Addition...");
        $this->Image = "04029.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 29;

        $this->initializeFaction("Montaigne");

        $this->WealthCost = 0;

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate("Extortion"),
            clienttranslate("Fortune"),
            clienttranslate("Villainous")
        ];

        $this->Text = clienttranslate("<b>En Garde Merchant Action:</b> Take control of target attachment equipped to an opposing character and equip it to your character at this location, paying all costs. Its last controller draws a card unless your <b>Leader</b> is a <b>Villain</b>");

        $this->resetCard();

        $this->Actions = [
            new Action_04029(),
        ];
    }
}
