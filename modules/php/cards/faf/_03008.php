<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03008;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03008;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03008 extends Risk implements IHasActions, IHasManeuvers, IRiskThatTargetsCharacters
{
    use ActionTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Arrogant");
        $this->Image = "03008.jpg";
        $this->ExpansionName = "faf";
        $this->ExpansionNumber = 3;
        $this->CardNumber = 8;

        $this->initializeFaction("Vodacce");

        $this->WealthCost = 1;

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate("Flourish"),
            clienttranslate("Hubris"),
            clienttranslate("Challenge")
        ];

        $this->Text = clienttranslate("<p><b>City Action:</b> Your performer issues a [Combat] challenge to target opposing character with equal or lower [Influence].</p><p><b>Gambling Maneuver:</b> If your participant has more [Influence] than the adversary • +1[Riposte] and draw a card.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03008(),
        ];

        $this->Maneuvers = [
            new Maneuver_03008(),
        ];
    }
}
