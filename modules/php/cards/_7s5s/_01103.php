<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01103a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01103b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01103;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01103 extends Risk implements IHasActions, IHasManeuvers
{
    use ActionTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Adaptable");
        $this->Image = "01103.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction("Castille");
        
        $this->WealthCost = 1;
        $this->Riposte = 0;
        $this->Parry = 0;
        $this->Thrust = 0;

        $this->Traits = [
            'Flourish',
            'Virtue',
            'Unique',
        ];

        $this->Text = "<p>Pirate City Action: If you are the first player • Claim your performer's location.</p><p>City Action: If you are not the first player • En garde your performer.</p><p>Maneuver: +2 [Riposte]. Then choose and gain: +2 [Parry]  or +2 [Thrust].</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_01103a(),
            new Action_01103b(),
        ];

        $this->Maneuvers = [
            new Maneuver_01103(),
        ];
    }
}