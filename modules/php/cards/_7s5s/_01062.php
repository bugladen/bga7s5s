<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01062;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01062;

class _01062 extends Leader implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Odette Dubois D'Arrent");
        $this->Image = "01062.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 62;

        $this->initializeFaction("Montaigne");
        $this->Title = "Genteel Spy";
        $this->Resolve = 5;
        $this->Combat = 1;
        $this->Finesse = 4;
        $this->Influence = 3;
        $this->CrewCap = 6;
        $this->Panache = 7;

        $this->ModifiedCrewCap = $this->CrewCap;
        $this->ModifiedPanache = $this->Panache;

        $this->Traits = [
            "Leader",
            "Hero",
            "Diplomat",
            "Montaigne",
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01062(),
        ];

        $this->Reactions = [
            new Reaction_01062(),
        ];
    }

}