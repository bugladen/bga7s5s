<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\maneuvers\Maneuver_05Stash;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Stash;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _05Stash extends Risk implements IHasReactions, IHasManeuvers, IRiskThatTargetsCharacters
{
    use ReactionTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Extra Rations";
        $this->Image = "05Stash.v5.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 27;

        $this->initializeFaction("Castille");

        $this->WealthCost = 0;

        $this->Riposte = 0;
        $this->Parry = 3;
        $this->Thrust = 2;

        $this->Traits = [
            "Revelry"
        ];

        $this->Text = "<p><b>City Reaction:</b> When a pressure you initiated fails, en garde target character at tha pressured location.</p>
<p><b>Covert Maneuver:</b> The adversary may discard an Attachment. If they do not, engage target opposing character.</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_05Stash(),
        ];

        $this->Maneuvers = [
            new Maneuver_05Stash(),
        ];
    }
}
