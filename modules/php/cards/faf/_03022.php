<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03022;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03022 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Overzealous');
        $this->Image = '03022.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 22;

        $this->initializeFaction('Eisen');

        $this->WealthCost = 1;

        $this->Riposte = 0;
        $this->Parry = 0;
        $this->Thrust = 4;

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Hubris'),
            clienttranslate('Faith'),
            clienttranslate('Zeal')
        ];

        $this->Text = clienttranslate("<p><b>Maneuver:</b> Final Strike • En garde target character at this location. If your participant was a Zealot or Hunter, draw a card.</p>
        <p><i>(Final Strike activates if your participant is destroyed the round this card is played.)</i></p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_03022(),
        ];
    }
}