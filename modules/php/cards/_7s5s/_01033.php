<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAmARiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01033 extends Risk implements IHasActions, IHasManeuvers, IAmARiskThatTargetsCharacters
{
    use ActionTrait;
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Veronica's Guile");
        $this->Image = 'img/cards/7s5s/033.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 33;

        $this->initializeFaction('Vodacce');

        $this->Riposte = 0;
        $this->Parry = 1;
        $this->Thrust = 0;

        $this->WealthCost = 1;

        $this->Traits = [
            'Challenge',
            'Flourish',
            'Cunning',
            'Ambrogia',
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01033(),
        ];

        $this->Maneuvers = [
            new Maneuver_01033(),
        ];
    }
}