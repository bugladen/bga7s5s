<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03052;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03052;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _03052 extends Character implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Yevgeni");
        $this->Title = clienttranslate("All-Seeing Cyclops");
        $this->Image = '03052.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 52;

        $this->InPlayXImageOffset = -20;

        $this->initializeFaction("Ussura");

        $this->Resolve = 6;
        $this->Combat = 4;
        $this->Finesse = 1;
        $this->Influence = 1;

        $this->Traits = [
            clienttranslate("Hero"),
            clienttranslate("Scion"),
            clienttranslate("Sorcerer"),
            clienttranslate("Exile"),
            clienttranslate("Ussura")
        ];

        $this->Text = clienttranslate("<p>At the beginning of Dusk, you may look at the top three cards of the City Deck. If you do, sink one and replace the others in any order.</p>
<p><b>Gambling Technique:</b> Look at your adversary's hand.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_03052(),
        ];

        $this->Techniques = [
            new Technique_03052(),
        ];
    }
}
