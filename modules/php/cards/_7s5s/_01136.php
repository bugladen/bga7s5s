<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01136;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01136;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01136 extends Risk implements IHasActions, IHasManeuvers
{
    use ActionTrait;
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("My Fight, Alone");
        $this->Image = "01136.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 136;
        $this->initializeFaction("Ussura");

        $this->WealthCost = 0;
        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Relentless'),
        ];

        $this->Text = clienttranslate("<p><b>City Action:</b> If your performer is the only character you control at this location • They heal a wound.</p><p><b>Maneuver:</b> If this is your only character at this location • +1 Riposte.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01136(),
        ];

        $this->Maneuvers = [
            new Maneuver_01136(),
        ];
    }

}