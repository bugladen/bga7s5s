<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03069a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03069b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03069 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Hop on Board");
        $this->Image = '03069.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 69;

        $this->initializeFaction('Neutral');
        
        $this->WealthCost = 1;

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->Traits = [
            clienttranslate('Relentless'),
            clienttranslate('Ad Hoc'),
            clienttranslate('Flourish')            
        ];

        $this->Text = clienttranslate("<p><b>Maneuver:</b> Swap your participant with your other character at this location.</p>
<p><b>Gambling Maneuver:</b> +1[Riposte] and swap your participant with your other character at this location.</p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_03069a(),
            new Maneuver_03069b(),
        ];
    }
}
