<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01086;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01086;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01086 extends Risk implements IHasActions, IHasManeuvers
{
    use ManeuverTrait;
    use ActionTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Status Matters");
        $this->Image = "01086.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction("Montaigne");
        
        $this->WealthCost = 1;
        $this->Riposte = 1;
        $this->Parry = 0;
        $this->Thrust = 1;

        $this->Traits = [
            'Flourish',
            'Demoralize',
            "Valroux",
        ];

        $this->resetCard();
        
        $this->Actions = [
            new Action_01086(),
        ];

        $this->Maneuvers = [
            new Maneuver_01086(),
        ];
    }
}