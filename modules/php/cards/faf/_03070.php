<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03070;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03070 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Comforting");
        $this->Image = '03070.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 70;

        $this->initializeFaction('Neutral');
        
        $this->WealthCost = 0;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 1;
        $this->Thrust = 4;

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Virtue')
        ];

        $this->Text = clienttranslate("<p><b>Maneuver</b>: Discard threat from your participant in excess of your adversary's stat value used for the duel.</p>
        <p><i>(Example: In an [Influence] duel, remove threat in excess of the adversary's [Influence])</i></p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_03070(),
        ];
    }
}
