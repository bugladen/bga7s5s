<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03035;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03035;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03035 extends Risk implements IHasManeuvers, IHasReactions, IRiskThatTargetsCharacters
{
    use ManeuverTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Loyal");
        $this->Image = '03035.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 35;

        $this->initializeFaction("Montaigne");

        $this->WealthCost = 0;

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate("Hubris"),
            clienttranslate("Camaraderie"),
            clienttranslate("Flourish")
        ];

        $this->Text = clienttranslate("<p><b>Reaction:</b> When a pressure occurs, if you control more non-<b>Mercenaries</b> at that location than each opponent • Add +1 to your total for the pressure.</p>
        <p><b>Maneuver:</b> Wound your other character at this location • +1[Riposte] or +2[Thrust].</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_03035(),
        ];

        $this->Maneuvers = [
            new Maneuver_03035(),
        ];
    }
}
