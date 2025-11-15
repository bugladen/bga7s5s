<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01124;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01124;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _01124 extends Character implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Ved'ma");
        $this->Image = "img/cards/7s5s/124.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 124;

        $this->Faction = "Ussura";
        $this->Title = "Ancient Witch";
        $this->Resolve = 3;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 3;

        $this->Traits = [
            "Sorcerer",
            "Ussura",
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01124(),
        ];

        $this->Reactions = [
            new Reaction_01124(),
        ];
    }

}