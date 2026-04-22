<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02049;

class _02049 extends Risk implements IHasActions, IHasManeuvers, IRiskThatTargetsCharacters
{
    use ActionTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Justice Served Cold");
        $this->Image = "02049.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 49;

        $this->initializeFaction("Ussura");

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 1;

        $this->WealthCost = 0;

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Heroic'),
            clienttranslate('Challenge'),
        ];

        $this->Text = clienttranslate("<p><b>City Action:</b> Your performer issues a [Combat] challenge to target opposing <b>Mercenary</b> or <b>Thug</b>.</p><p><b>Maneuver:</b> If the adversary is a <b>Mercenary</b> or <b>Thug</b> • +1[Riposte].</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_02049(),
        ];

        $this->Maneuvers = [
            new Maneuver_02049(),
        ];
    }

}
