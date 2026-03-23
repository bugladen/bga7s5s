<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01142;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01142 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Sunder");
        $this->Image = "01142.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction("Ussura");

        $this->WealthCost = 1;
        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 2;

        $this->Traits = [
            'Flouish',
            'Demoralize',
        ];

        $this->Text = "<p>Maneuver: If you have greater or equal [Combat] than the adversary • Destroy target attachment on them.</p>";

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_01142(),
        ];
    }

}