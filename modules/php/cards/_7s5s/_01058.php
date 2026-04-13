<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01058 extends Risk implements IHasActions, IHasManeuvers, IRiskThatTargetsCharacters
{
    use ActionTrait;
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Press the Advantage");
        $this->Image = "01058.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 58;
        $this->initializeFaction('Eisen');
        
        $this->WealthCost = 2;
        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Relentless'),
            clienttranslate('Drexel'),
        ];

        $this->Text = clienttranslate("<p><b>City Action:</b> Target an opposing non-Leader character with lower [Combat] than your performer • Engage them and move them Home.</p><p><b>Maneuver:</b> +1 [Thrust] and engage the adversary. If they are already engaged, +2 [Thrust] instead</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01058(),
        ];

        $this->Maneuvers = [
            new Maneuver_01058(),
        ];
    }
}