<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01133;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAmARiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01133;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01133;

class _01133 extends Risk implements IHasActions, IHasReactions, IHasManeuvers, IAmARiskThatTargetsCharacters
{
    use ActionTrait;
    use ReactionTrait;
    use ManeuverTrait;

    public bool $WillEngage = false;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Matushka's Efficiency");
        $this->Image = "img/cards/7s5s/133.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = "Ussura";

        $this->WealthCost = 1;
        $this->Riposte = 2;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 1;

        $this->Traits = [
            'Sorcery',
            'Dar Matushki',
        ];

        $this->WillEngage = false;

        $this->resetCard();

        $this->Actions = [
            new Action_01133(),
        ];

        $this->Reactions = [
            new Reaction_01133(),
        ];

        $this->Maneuvers = [
            new Maneuver_01133(),
        ];
    }

}