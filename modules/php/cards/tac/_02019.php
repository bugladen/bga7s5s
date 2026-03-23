<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02019;

class _02019 extends Risk implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Trial of Faith");
        $this->Image = "02019.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 19;

        $this->initializeFaction("Eisen");

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->WealthCost = 1;

        $this->Traits = [
            "Faith",
            "Zeal",
        ];

        $this->Text = clienttranslate("<p><b>City Reaction:</b> When your performer's location is pressured with [inf], wound them • Each player adds +1 to their total for each wound on their characters at this location. Then, if your performer is a <b>Zealot</b>, they may heal a wound.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_02019(),
        ];
    }
}