<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03009;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03009;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03009 extends Risk implements IHasActions, IHasManeuvers
{
    use ActionTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Follow the Thread");
        $this->Image = "03009.jpg";
        $this->ExpansionName = "faf";
        $this->ExpansionNumber = 3;
        $this->CardNumber = 9;

        $this->initializeFaction("Vodacce");

        $this->WealthCost = 0;

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate("Flourish"),
            clienttranslate("Sorcery"),
            clienttranslate("Sorte")
        ];

        $this->Text = clienttranslate("<p><b>Sorcerer Strega Action:</b> Move your performer to an adjacent location where there is an enemy character or an available <b>Mercenary</b>.</p><p><b>Strega Maneuver:</b> -1[Thrust] • Wound the adversary.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03009(),
        ];

        $this->Maneuvers = [
            new Maneuver_03009(),
        ];
    }
}
