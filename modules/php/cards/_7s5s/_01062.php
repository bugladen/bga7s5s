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
        $this->Title = clienttranslate("Genteel Spy");
        $this->Resolve = 5;
        $this->Combat = 1;
        $this->Finesse = 4;
        $this->Influence = 3;
        $this->CrewCap = 6;
        $this->Panache = 7;

        $this->ModifiedCrewCap = $this->CrewCap;
        $this->ModifiedPanache = $this->Panache;

        $this->Traits = [
            clienttranslate("Leader"),
            clienttranslate("Hero"),
            clienttranslate("Diplomat"),
            clienttranslate("Montaigne"),
        ];

        $this->Text = clienttranslate("<p>When Odette is challenged, if you have an en garde Musketeer at this location, they may intervene without engaging.</p><p><b>City Action:</b> Move your adjacent Duelist to this location.</p><p><b>Reaction:</b> When a challenge is accepted at this location • Move an adjacent Renown to this location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01062(),
        ];

        $this->Reactions = [
            new Reaction_01062(),
        ];
    }

}