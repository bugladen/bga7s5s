<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01173;

class _01173 extends Risk implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Sea Legs");
        $this->Image = "01173.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 173;

        $this->WealthCost = 1;
        $this->Riposte = 1;
        $this->Parry = 0;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate('Savvy'),
        ];

        $this->Text = clienttranslate("<p><b>Reaction:</b> After your character moves to a City location • Move them again to an adjacent City location.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01173(),
        ];
    }
}