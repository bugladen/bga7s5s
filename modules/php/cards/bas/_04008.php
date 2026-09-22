<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04008;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _04008 extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Fate's Silence");
        $this->Image = "04008.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 8;

        $this->initializeFaction("Vodacce");

        $this->Riposte = 1;
        $this->Parry = 2;
        $this->Thrust = 1;

        $this->WealthCost = 0;

        $this->Traits = [
            clienttranslate("Sorcery"),
            clienttranslate("Sorte"),
            clienttranslate("Unique"),
        ];

        $this->Text = clienttranslate("<p><b>Forced:</b> At the end of High Drama, if this card is equipped • Destroy it.</p>
<p><b>Sorcerer Strega Action:</b> Equip this card to target opposing non-<b>Leader</b>. This ability cannot be copied.</p>
<p>The equipped character treats their text box as blank. <i>(They cannot use any of their abilities).</i></p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04008(),
        ];
    }
}
