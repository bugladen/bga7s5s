<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04018;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _04018 extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Seek Each Devil");
        $this->Image = "04018.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 18;

        $this->initializeFaction("Eisen");

        $this->WealthCost = 1;

        $this->Riposte = 0;
        $this->Parry = 1;
        $this->Thrust = 4;

        $this->Traits = [
            clienttranslate("Hunt"),
            clienttranslate("Faith"),
            clienttranslate("Relentless")
        ];

        $this->Text = clienttranslate("<p>While your performer is an <b>Academic</b> or <b>Hunter</b>, this card has -1 cost.</p>
<p><b>En Garde Action:</b> Target an enemy character at an adjacent <b>City</b> location • Move your performer there. Then, each other player who controls a <b>Sorcerer</b> or <b>Monster</b> there discards a card.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04018(),
        ];
    }
}
