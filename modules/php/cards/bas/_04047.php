<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _04047 extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Celerity');
        $this->Image = '04047.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 47;

        $this->initializeFaction("Ussura");

        $this->WealthCost = 0;

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate("Challenge"),
        ];

        $this->Text = clienttranslate("<p><b>Duelist City Action:</b> Engage your performer • They issue a [Finesse] challenge to target opposing character. Only characters with 3[Finesse] or more can intervene. These effects cannot be cancelled.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04047(),
        ];
    }

    public function effectsCannotBeCancelled(): bool
    {
        return true;
    }
}
