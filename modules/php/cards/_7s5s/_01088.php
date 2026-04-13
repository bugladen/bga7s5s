<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01088;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01088;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01088 extends Risk implements IHasManeuvers, IHasReactions
{
    use ManeuverTrait;
    use ReactionTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("You're Embarrassing Yourself");
        $this->Image = "01088.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 88;
        $this->initializeFaction("Montaigne");

        $this->WealthCost = 0;
        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Demoralize'),
            clienttranslate('Valroux'),
        ];

        $this->Text = clienttranslate("<p><b>Reaction:</b> When a challenge is issued by an opposing Mercenary • Cancel it.</p><p><b>Maneuver:</b> If the adversary is a Mercenary • +1 Riposte.</p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_01088(),
        ];

        $this->Reactions = [
            new Reaction_01088(),
        ];
    }
}