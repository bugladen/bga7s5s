<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _04058 extends Risk implements IHasReactions, IHasManeuvers, IRiskThatTargetsCharacters
{
    use ReactionTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Shield Rite');
        $this->Image = '04058.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 58;

        $this->initializeFaction('Neutral');

        $this->WealthCost = 0;

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate('Sorcery'),
            clienttranslate('Shamanism'),
            clienttranslate('Rite')
        ];

        $this->Text = clienttranslate("<p><b>En Garde Sorcerer Reaction:</b> When an opponent's ability would wound your performer • Wound target opposing character instead.</p>
<p><b>Sorcerer Maneuver:</b> If the adversary is engaged • +1[Riposte]. If your participant is en garde, draw a card.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_04058(),
        ];

        $this->Maneuvers = [
            new Maneuver_04058(),
        ];
    }
}
