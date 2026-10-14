<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Volk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _05Volk extends Character implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = "Volk";
        $this->Image = "05Volk.v4.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 12;

        $this->initializeFaction("Ussura");

        $this->Resolve = 3;
        $this->Combat = 3;
        $this->Finesse = 1;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->Traits = [
            "Animal",
            "Wolf",
            "Familiar",
        ];

        $this->Text = "<p>Volk cannot be included in Approach decks.</p>
<p>Volk cannot be equipped and cannot perform abilities on risks and schemes.</p>
<p><b>Reaction:</b> After an opposing character moved • Move Volk to their new location.</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_05Volk(),
        ];
    }

    // WHY: Printed Approach ban — DeckValidator + PutIntoApproachDeck eventCheckCore
    // (Damned / Orel / Brute shape). Always false (structural deck rule, not Silence-blankable).
    public function canEnterApproachDeck(): bool
    {
        return false;
    }

    // WHY: Printed "cannot be equipped". When Fate's Silence blanks the text box the
    // restriction lifts (abilitiesAreBlanked → true → can receive).
    public function canReceiveAttachments(): bool
    {
        return $this->abilitiesAreBlanked();
    }

    // WHY: Printed "cannot perform abilities on risks and schemes". Same Silence lift.
    public function canPerformRiskAndSchemeAbilities(): bool
    {
        return $this->abilitiesAreBlanked();
    }
}
