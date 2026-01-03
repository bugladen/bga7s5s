<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01090;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01090;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01090;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01090 extends Character implements IHasActions, IHasReactions, IHasTechniques
{
    use ActionTrait;
    use ReactionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Lorenzo de Zepeda");
        $this->Image = "img/cards/7s5s/090.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 90;

        $this->initializeFaction("Castille");
        $this->Title = "Bad News";
        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 4;
        $this->Influence = 1;

        $this->Traits = [
            "Duelist",
            "Scoundrel",
            "Castille",
        ];

        $this->Actions = [
            new Action_01090(),
        ];

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01090(),
        ];

        $this->Techniques = [
            new Technique_01090(),
        ];
    }
}

