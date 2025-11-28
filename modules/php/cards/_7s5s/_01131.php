<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01131;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01131;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAmARiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01131 extends Risk implements IHasActions, IHasManeuvers, IAmARiskThatTargetsCharacters
{
    use ActionTrait;
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Iron and Velvet");
        $this->Image = "img/cards/7s5s/131.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction("Ussura");

        $this->WealthCost = 1;
        $this->Riposte = 0;
        $this->Parry = 1;
        $this->Thrust = 2;

        $this->Traits = [
            'Challenge',
            'Kulachniy Boi',
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01131(),
        ];

        $this->Maneuvers = [
            new Maneuver_01131(),
        ];
    }

}