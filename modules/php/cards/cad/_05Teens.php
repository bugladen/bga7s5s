<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Teens;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

// WHY IRiskThatTargetsCharacters: the print says "target opposing character's equipped
// attachment" and the first chooser is a character (Rules Team / Cesca marker). Not
// IAbilityThatTargetsCards - the attachment is picked from the chosen character, not as the target.
class _05Teens extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Smash and Grab";
        $this->Image = "05Teens.v4.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 19;

        $this->initializeFaction("Vodacce");

        $this->WealthCost = 0;

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 2;

        $this->Traits = [
            "Gang",
            "Crime",
            "Betrayal"
        ];

        $this->Text = "<p><b>Covert Action:</b> Engage your performer • Destroy target opposing character's equipped attachment. You may take control of it and equip it to your character paying all costs.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_05Teens(),
        ];
    }
}
