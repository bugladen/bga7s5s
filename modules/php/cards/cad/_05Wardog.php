<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Wardoga;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Wardogb;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _05Wardog extends FactionAttachment implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Smuggled Long Rifle";
        $this->Image = "05Wardog.v4.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 28;

        $this->initializeFaction("Eisen");

        $this->WealthCost = 1;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->Traits = [
            "Weapon",
            "Ranged",
            "Rifle",
            "Stealth",
            "Contraband"
        ];

        $this->Text = "<p><b>Covert Reaction:</b> After an enemy moves to an adjacent <b>City</b> location • Wound them.</p>
<p><b>Covert Reaction:</b> When a pressure occurs at an adjacent <b>City</b> location • Add +1 to your total for that pressure.</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_05Wardoga(),
            new Reaction_05Wardogb(),
        ];
    }
}
