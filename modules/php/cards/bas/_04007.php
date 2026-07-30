<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04007a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04007b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _04007 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Bernoulli's Approach");
        $this->Image = "04007.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 7;

        $this->initializeFaction("Vodacce");

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->WealthCost = 1;

        $this->Traits = [
            clienttranslate("Flourish"),
            clienttranslate("Bernoulli")
        ];

        $this->Text = clienttranslate("<p>While the adversary has more wounds than your participant, this card has -1 cost.</p>
<p><b>Duelist Maneuver:</b> -3[Thrust] • +2[Riposte].</p>
<p><b>Duelist Maneuver:</b> -1[Parry] • +2[Thrust]</p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_04007a(),
            new Maneuver_04007b(),
        ];
    }
}
