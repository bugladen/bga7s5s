<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02059;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02059;

class _02059 extends Risk implements IHasReactions, IHasManeuvers
{
    use ReactionTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Quick Reflexes');
        $this->Image = '02059.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 59;

        $this->initializeFaction('Neutral');

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->WealthCost = 0;

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Ad Hoc')
        ];

        $this->Text = clienttranslate("<p><b>Reaction:</b> When an opponent's ability wounds your character • Ignore that wound. <i>(The wound is not taken.)</i></p><p><b>Maneuver:</b> If your participant has 3[Finesse] or more • +1[Riposte].</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_02059(),
        ];

        $this->Maneuvers = [
            new Maneuver_02059(),
        ];
    }
}