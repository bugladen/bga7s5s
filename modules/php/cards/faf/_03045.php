<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03045;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03045;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03045 extends Risk implements IHasActions, IHasManeuvers
{
    use ActionTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Curious");
        $this->Image = '03045.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 45;

        $this->initializeFaction("Castille");

        $this->WealthCost = 0;

        $this->Riposte = 1;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate("Hubris"),
            clienttranslate("Flourish")
        ];

        $this->Text = clienttranslate("<p><b>Action:</b> Wound your performer • Move them to an adjacent location controlled by an opponent.</p>
        <p><b>Gambling Maneuver:</b> Wound your participant • +2 [Riposte].</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03045(),
        ];

        $this->Maneuvers = [
            new Maneuver_03045(),
        ];
    }
}
