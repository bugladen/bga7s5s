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
        $this->Image = "01118.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 118;

        $this->initializeFaction("Ussura");
        $this->Title = clienttranslate("Slient Schemer");
        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->Traits = [
            clienttranslate("Sorcerer"),
            clienttranslate("Ussura"),
        ];

        $this->Text = clienttranslate("<p>City Action: Move Elina to an adjacent City location. If she is opposed at the new location, en garde her.</p><p>City Reaction: After Elina performs a Sorcerer ability • Move a Renown to this location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01118(),
        ];

        $this->Reactions = [
            new Reaction_01118(),
        ];
    }

}