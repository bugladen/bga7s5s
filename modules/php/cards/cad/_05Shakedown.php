<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Shakedown;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _05Shakedown extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Blackmail";
        $this->Image = "05Shakedown.v5.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 28;

        $this->initializeFaction("Castille");

        $this->WealthCost = 1;

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 1;

        $this->Traits = [
            "Discovery",
            "Duress",
            "Stealth",
        ];

        $this->Text = "<p>If your performer is a <b>Scoundrel</b> or <b>Villain</b>, this card has -1 cost.</p>
<p><b>Covert Action:</b> Equip this card to target opposing character.</p>
<p>The equipped character has -1 [Influence] and gains: <b>Action:</b> Engage this character • Destroy this card.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_05Shakedown(),
        ];
    }
}
