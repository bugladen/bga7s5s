<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04028;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _04028 extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Depose");
        $this->Image = "04028.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 28;

        $this->initializeFaction("Montaigne");

        $this->WealthCost = 1;

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 0;

        $this->Traits = [
            clienttranslate("Duty"),
            clienttranslate("Arrest"),
            clienttranslate("Justice"),
        ];

        $this->Text = clienttranslate("<p><b>En Garde Musketeer Action:</b> Target an opposing character • Move them and your performer to a <b>City</b> location you control, or one where you control a <b>Leader</b>.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04028(),
        ];
    }
}
