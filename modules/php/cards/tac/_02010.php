<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02010;

class _02010 extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Twist of the Arcana");
        $this->Image = "02010.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 10;

        $this->initializeFaction("Vodacce");

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 1;

        $this->WealthCost = 0;

        $this->Traits = [
            "Sorcery",
            "Sorte",
        ];

        $this->Text = "<p><b>Sorcerer Strega City Action:</b> Target two of your characters at your performer's location • Move up to two wounds between them.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_02010(),
        ];
    }
}