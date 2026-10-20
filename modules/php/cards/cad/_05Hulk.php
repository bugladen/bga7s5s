<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Hulk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _05Hulk extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Rage";
        $this->Image = "05Hulk.v4.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 15;

        $this->initializeFaction("Eisen");

        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->Parry = 0;
        $this->Thrust = 5;

        $this->Traits = [
            "Challenge",
            "Zeal",
            "Relentless",
        ];

        $this->Text = "<p><b>City Action:</b> Engage your performer. Wound them unless they are a Berserker • They issue an unrefusable [Combat] challenge to target opposing character. The adversary's first combat has -2 [Parry]. These effects cannot be cancelled.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_05Hulk(),
        ];
    }

    // WHY: Printed "These effects cannot be cancelled." Night of Drinking already
    // skips Risks that return true here (same hook as Celerity / Unsanctioned Duel).
    public function effectsCannotBeCancelled(): bool
    {
        return true;
    }
}
