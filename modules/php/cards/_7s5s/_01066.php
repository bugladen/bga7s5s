<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01066;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01066;

class _01066 extends Character implements IHasReactions, IHasTechniques
{
    use ReactionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Horatio Lockwood");
        $this->Image = "01066.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 66;

        $this->initializeFaction("Montaigne");
        $this->Title = clienttranslate("Smirking Rake");
        $this->Resolve = 5;
        $this->Combat = 3;
        $this->Finesse = 3;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->Traits = [
            clienttranslate("Duelist"),
            clienttranslate("Scoundrel"),
            clienttranslate("Avalon"),
        ];

        $this->Text = clienttranslate("<p>City Reaction: After an opposing character moved to an adjacent location • Move Horatio to their new location.</p><p>Technique: If the adversary is the only enemy character at this location • +2 Thrust.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01066(),
        ];

        $this->Techniques = [
            new Technique_01066(),
        ];
    }
}