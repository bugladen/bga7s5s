<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03011 extends Risk implements IHasActions, IHasManeuvers, IRiskThatTargetsCharacters
{
    use ActionTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Provoking the Pack");
        $this->Image = "03011.jpg";
        $this->ExpansionName = "faf";
        $this->ExpansionNumber = 3;
        $this->CardNumber = 11;

        $this->initializeFaction("Vodacce");

        $this->WealthCost = 0;

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate("Flourish"),
            clienttranslate("Camaraderie"),
            clienttranslate("Gang")
        ];

        $this->Text = clienttranslate("<p><b>City Action:</b> If your performer is opposed • Move your adjacent <b>Thug</b> or <b>Bodyguard</b> to this location.</p><p><b>Gambling Maneuver:</b> If you control a <b>Thug</b> or <b>Bodyguard</b> at this location • +1[Riposte].</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03011(),
        ];

        $this->Maneuvers = [
            new Maneuver_03011(),
        ];
    }
}
