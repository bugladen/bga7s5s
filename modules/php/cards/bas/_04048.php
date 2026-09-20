<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04048a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04048b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _04048 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Iaijutsu Strike');
        $this->Image = '04048.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 48;

        $this->initializeFaction("Ussura");

        $this->WealthCost = 1;

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate("Naito-ryu"),
            clienttranslate("Kenjutsu"),
            clienttranslate("Flourish")
        ];

        $this->Text = clienttranslate("<p><b>Duelist Maneuver:</b> +1[Riposte]. If this location is uncontrolled, claim it.</p>
<p><b>Duelist Maneuver:</b> +1[Riposte]. If this location is controlled, it becomes uncontrolled.</p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_04048a(),
            new Maneuver_04048b(),
        ];
    }
}
