<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Excalibur;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques\Technique_05Excalibur;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;

class _05Excalibur extends FactionAttachment implements IHasReactions, IHasTechniques
{
    use ReactionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Dietrich Blade";
        $this->Title = "Honored Heirloom";
        $this->Image = "05Excalibur.v4.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 27;

        $this->initializeFaction("Eisen");

        $this->WealthCost = 1;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 2;

        $this->Traits = [
            "Weapon",
            "Melee",
            "Sword",
            "Relic",
            "Unique"
        ];

        $this->Text = "<b>Reaction:</b> When the equipped performer issues a challenge • Wound the performer and target opposing character with equal or greater [Combat].
<p><b>Technique:</b> +1 [Riposte].</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_05Excalibur(),
        ];

        $this->Techniques = [
            new Technique_05Excalibur(),
        ];
    }
}
