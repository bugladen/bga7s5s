<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03048 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wily");
        $this->Image = '03048.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 48;

        $this->initializeFaction("Castille");

        $this->WealthCost = 1;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate("Virtue"),
            clienttranslate("Flourish")
        ];

        $this->Text = clienttranslate("<p>If this card was gambled, it has -1 cost.</p>
<p><b>Scoundrel Maneuver:</b> Move all threat from your participant to the adversary.</p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_03048(),
        ];
    }
}
