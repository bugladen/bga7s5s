<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02039;

class _02039 extends Risk implements IHasReactions, IHasManeuvers
{
    use ReactionTrait;
    use ManeuverTrait;
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Raise the Stakes');
        $this->Image = '02039.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 39;

        $this->initializeFaction('Castille');

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->WealthCost = 0;

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Aldana'),
            clienttranslate('Duress'),
        ];

        $this->Text = clienttranslate("<p><b>Reaction:</b> When the adversary announces their combat card • Add a threat to both participants.</p><p><b>Maneuver:</b> At the end of the round • Add a threat to both participants.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_02039(),
        ];

        $this->Maneuvers = [
            new Maneuver_02039(),
        ];
    }
}