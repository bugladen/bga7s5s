<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01115;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01115;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01115 extends Risk implements IHasActions, IHasManeuvers, IRiskThatTargetsCharacters
{
    use ManeuverTrait;
    use ActionTrait;
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Taunt");
        $this->Image = "01115.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction("Castille");
        
        $this->WealthCost = 1;
        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 1;
        $this->Thrust = 2;

        $this->Traits = [
            'Flourish',
            'Demoralize',
            'Torres',
        ];

        $this->Text = "<p>City Action: Target an adjacent enemy character • Move them to your performer's location.</p><p>Maneuver: If your participant has more [Finesse] than the adversary • They discard a card.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_01115(),
        ];

        $this->Maneuvers = [
            new Maneuver_01115(),
        ];
    }
}