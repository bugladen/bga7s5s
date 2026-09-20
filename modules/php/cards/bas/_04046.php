<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04046;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04046;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _04046 extends Risk implements IHasReactions, IHasManeuvers
{
    use ReactionTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Bravado');
        $this->Image = '04046.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 46;

        $this->initializeFaction("Ussura");

        $this->WealthCost = 0;

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate("Pride"),
            clienttranslate("Flourish")
        ];

        $this->Text = clienttranslate("<p><b>Duelist Reaction:</b> At the end of your adversary's round of a duel • Add a threat to your <b>Duelist</b> participant.
<br><i>(The duel ends only if there is no threat remaining in any threat pool.)</i></p>
<p><b>Duelist Maneuver:</b> +1[Parry]</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_04046(),
        ];

        $this->Maneuvers = [
            new Maneuver_04046(),
        ];
    }
}
