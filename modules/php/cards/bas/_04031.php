<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04031;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04031;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _04031 extends Character implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Andare de Castillo");
        $this->Title = clienttranslate("Defender of the Faith");
        $this->Image = "04031.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 31;

        $this->initializeFaction("Castille");

        $this->InPlayXImageOffset = 20;

        $this->Resolve = 5;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate("Hero"),
            clienttranslate("Duelist"),
            clienttranslate("Zealot"),
            clienttranslate("Castille")
        ];

        $this->Text = clienttranslate("<p><b>En Garde Reaction:</b> When a duel occurs at this location, at the beginning of the first round • Remove one threat from your participant.</p>
<p><b>Technique:</b> +1[Riposte]. If Andare is engaged, gain Lethal.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_04031(),
        ];

        $this->Techniques = [
            new Technique_04031(),
        ];
    }
}
