<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04016;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04016;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;

class _04016 extends FactionAttachment implements IHasReactions, IHasTechniques
{
    use ReactionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Drachenblut");
        $this->Image = "04016.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 16;

        $this->initializeFaction("Eisen");

        $this->WealthCost = 0;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate("Sorcery"),
            clienttranslate("Unguent"),
            clienttranslate("Invigorant")
        ];

        $this->Text = clienttranslate("<p><b>Reaction:</b> After a <b>Hunter</b> or <b>Berserker</b> equips this card • They heal a wound.</p>
<p><b>Gambling Technique:</b> At the end of your round, each participant gains a threat.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_04016(),
        ];

        $this->Techniques = [
            new Technique_04016(),
        ];
    }
}
