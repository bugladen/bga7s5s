<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01114;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01114 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Roll the Bones");
        $this->Image = "01114.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction("Castille");
        
        $this->WealthCost = 0;
        $this->Riposte = 1;
        $this->Parry = 0;
        $this->Thrust = 0;

        $this->Traits = [
            'Flourish',
            'Cheating',
        ];

        $this->Text = "<p>Maneuver: Gamble for free and reveal an additional card if your participant is a Scoundrel. Add the chosen card's combat values to this one instead of playing it. Sink all gambled cards. (Gambling for free does not count against your total gambles in a duel.)</p>";

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_01114(),
        ];
    }
}