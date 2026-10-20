<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05BoyBand;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _05BoyBand extends Risk implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "La Marea Di Ambrogia";
        $this->Image = "05BoyBand.v4.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 18;

        $this->initializeFaction("Vodacce");

        $this->WealthCost = 1;

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 2;

        $this->Traits = [
            "Flourish",
            "Cunning",
            "Ambrogia"
        ];

        $this->Text = "<p><b>Reaction</b>: When the adversary announces a combat card • Swap your participant with your <b>Thug</b> or <b>Duelist</b> at this location. Then, add a threat to both participants.</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_05BoyBand(),
        ];
    }
}