<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03023;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03023;

class _03023 extends Risk implements IHasActions, IHasManeuvers
{
    use ActionTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Second Wind');
        $this->Image = '03023.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 23;

        $this->initializeFaction('Eisen');

        $this->WealthCost = 0;

        $this->Riposte = 1;
        $this->Parry = 0;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate('Relentless'),
            clienttranslate('Zeal')
        ];

        $this->Text = clienttranslate("<p><b>City Action:</b> If your performer has two or more wounds • They heal a wound.</p>
        <p><b>Gambling Maneuver:</b> Your participant's threat is not converted to wounds this round unless your adversary is absent. <i>(The threat remains for your next round.)</i></p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03023(),
        ];

        $this->Maneuvers = [
            new Maneuver_03023(),
        ];
    }
}