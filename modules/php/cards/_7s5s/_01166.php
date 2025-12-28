<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01166;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;

class _01166 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("I'm Done With You");
        $this->Image = "img/cards/7s5s/166.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;

        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 1;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->Traits = [
            'Flourish',
            'Demoralize',
        ];

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_01166(),
        ];
    }
}