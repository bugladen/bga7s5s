<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01135;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01135;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01135 extends Risk implements IHasReactions, IHasManeuvers
{
    use ReactionTrait;
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Mireli's Revision");
        $this->Image = "01135.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction("Ussura");

        $this->WealthCost = 1;
        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 1;

        $this->Traits = [
            'Flourish',
            'Mireli',
        ];

        $this->Text = "<p>Reaction: When the adversary announces their combat card • Discard it. They gamble and play one for free instead. (It does not count against their total played gambles.)</p><p>Maneuver: Choose one: +2 [Parry], or wound the adversary and during their next, round their combat card has -2 [Thrust].</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01135(),
        ];

        $this->Maneuvers = [
            new Maneuver_01135(),
        ];
    }

}