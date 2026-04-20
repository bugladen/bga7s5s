<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02057;

class _02057 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Collateral Damage');
        $this->Image = '02057.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 57;

        $this->initializeFaction('Neutral');

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->WealthCost = 1;

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Villainous')
        ];

        $this->Text = clienttranslate("<b>Maneuver:</b> Wound a character at this location that is not a participant in the duel. If they have Brute, wound them again.");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_02057()
        ];
    }
} 