<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03024;

class _03024 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Superstitious');
        $this->Image = '03024.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 24;

        $this->initializeFaction('Eisen');

        $this->WealthCost = 1;

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Hubris'),
            clienttranslate('Faith')
        ];

        $this->Text = clienttranslate("<p><b>Maneuver:</b> If the adversary is a <b>Sorcerer</b> or <b>Monster</b> • +2 [parry] or +2 [thrust].</p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_03024(),
        ];
    }
}


