<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01118;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01118;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _01118 extends Character implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Elina Georginova");
        $this->Image = "img/cards/7s5s/118.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 118;

        $this->Faction = "Ussura";
        $this->Title = "Slient Schemer";
        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->Traits = [
            "Sorcerer",
            "Ussura",
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01118(),
        ];

        $this->Reactions = [
            new Reaction_01118(),
        ];
    }

}