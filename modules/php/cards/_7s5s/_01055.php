<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01055 extends Risk implements IHasActions, IHasManeuvers, IRiskThatTargetsCharacters
{
    use ActionTrait;
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Last Word");
        $this->Image = "01055.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction('Eisen');
        
        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->Parry = 1;
        $this->Thrust = 2;

        $this->Traits = [
            'Flourish',
            'Ranged',
        ];

        $this->Text = clienttranslate("<p>City Action: If your performer is equipped with a Ranged Weapon • Move target opposing character to an adjacent City location.</p><p>Maneuver: If your participant is equipped with a Ranged Weapon • Wound the adversary.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01055(),
        ];

        $this->Maneuvers = [
            new Maneuver_01055(),
        ];
    }
}