<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03034;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03034 extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("La Voix des Sans Voix");
        $this->Image = '03034.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 34;

        $this->initializeFaction("Montaigne");

        $this->WealthCost = 0;

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate("Inspiring"),
            clienttranslate("Camaraderie"),
            clienttranslate("Unique")
        ];

        $this->Text = clienttranslate("<b>Diplomat City Action:</b> Engage your performer • En garde another character you control at this location. Then, that character may heal a wound. If they do not, draw a card.");

        $this->resetCard();

        $this->Actions = [
            new Action_03034(),
        ];
    }
}
