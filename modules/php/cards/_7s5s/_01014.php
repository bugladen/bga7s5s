<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01014;

class _01014 extends Character implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Vittoria Anselmo");
        $this->Image = "img/cards/7s5s/014.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 14;

        $this->initializeFaction("Vodacce");
        $this->Title = "Love-Struck Enforcer";
        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 2;

        $this->Traits = [
            "Duelist",
            "Red Hand",
            "Vodacce",
        ];

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01014(),
        ];
    }
}