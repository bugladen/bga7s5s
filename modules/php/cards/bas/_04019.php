<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04019;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _04019 extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("No More Words!");
        $this->Image = "04019.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 19;

        $this->initializeFaction("Eisen");

        $this->WealthCost = 1;

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate("Challenge"),
            clienttranslate("Eisenfaust"),
            clienttranslate("Zeal"),
        ];

        $this->Text = clienttranslate("<p><b>En Garde Action:</b> Engage a <b>Melee Weapon</b> or <b>Eisenfaust</b> attachment equipped to your performer • They issue a [Combat] challenge to target opposing character.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04019(),
        ];
    }
}
