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
        $this->Image = "img/cards/7s5s/066.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 66;

        $this->initializeFaction("Montaigne");
        $this->Title = "Smirking Rake";
        $this->Resolve = 5;
        $this->Combat = 3;
        $this->Finesse = 3;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->Traits = [
            "Duelist",
            "Scoundrel",
            "Avalon",
        ];

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01066(),
        ];

        $this->Techniques = [
            new Technique_01066(),
        ];
    }
}