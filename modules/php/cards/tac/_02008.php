<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02008;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _02008 extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Fate's Kiss");
        $this->Image = "02008.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 8;

        $this->initializeFaction("Vodacce");

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->WealthCost = 0;

        $this->Traits = [
            "Sorcery",
            "Sorte",
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_02008(),
        ];
    }
}