<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Teapot;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _05Teapot extends Risk implements IHasReactions, IRiskThatTargetsCharacters
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Tempest";
        $this->Image = "05Teapot.v4.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 17;

        $this->initializeFaction("Ussura");

        $this->WealthCost = 0;

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->Traits = [
            "Sorcery",
            "Shamanism",
            "Rite"
        ];

        $this->Text = "<p><b>En Garde Sorcerer Reaction:</b> After your performer moves to a <b>City</b> location • Move target opposing character <b>Home</b>.</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_05Teapot(),
        ];
    }
}
