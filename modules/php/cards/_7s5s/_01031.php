<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01031;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01031 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Rough 'em Up");
        $this->Image = '01031.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 31;

        $this->initializeFaction('Vodacce');

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 0;
        $this->Thrust = 3;

        $this->WealthCost = 0;

        $this->Traits = [
            'Flourish',
            'Brawl',
            'Gang',
            'Zeal',
        ];

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_01031(),
        ];

    }
}