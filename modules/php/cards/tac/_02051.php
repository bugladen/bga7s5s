<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02051;

class _02051 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Voice of the Gods");
        $this->Image = "02051.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 51;

        $this->initializeFaction("Ussura");

        $this->Riposte = 0;
        $this->Parry = 0;
        $this->Thrust = 4;

        $this->WealthCost = 0;

        $this->Traits = [
            clienttranslate('Sorcery'),
            clienttranslate('Galdr'),
            clienttranslate('Storte Merke'),
        ];

        $this->Text = clienttranslate("<p><b>Sorcerer City Action:</b> Engage your performer • En garde target character at your performer's location. Send this card to <b>The Locker</b>.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_02051(),
        ];
    }
}