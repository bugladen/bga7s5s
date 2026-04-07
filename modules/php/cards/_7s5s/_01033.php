<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01033 extends Risk implements IHasActions, IHasManeuvers, IRiskThatTargetsCharacters
{
    use ActionTrait;
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Veronica's Guile");
        $this->Image = '01033.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 33;

        $this->initializeFaction('Vodacce');

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 1;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->WealthCost = 1;

        $this->Traits = [
            clienttranslate('Challenge'),
            clienttranslate('Flourish'),
            clienttranslate('Cunning'),
            clienttranslate('Ambrogia'),
        ];

        $this->Text = clienttranslate("<p><b>City Action:</b> Your performer issues an [Influence] challenge to target opposing character.</p><p><b>Maneuver:</b> If you have more [Influence] than the adversary • Move them Home.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01033(),
        ];

        $this->Maneuvers = [
            new Maneuver_01033(),
        ];
    }
}