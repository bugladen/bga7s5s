<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03012 extends Risk implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Subtle");
        $this->Image = "03012.jpg";
        $this->ExpansionName = "faf";
        $this->ExpansionNumber = 3;
        $this->CardNumber = 12;

        $this->initializeFaction("Vodacce");

        $this->WealthCost = 0;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 3;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate("Sorcery"),
            clienttranslate("Sorte"),
            clienttranslate("Virtue"),
            clienttranslate("Cunning")
        ];

        $this->Text = clienttranslate("<p><b>Sorcerer Strega Reaction:</b> When your performer intervenes • The challenge becomes an [Influence] challenge. <i>(Use [Influence] for restricted hostilities, including all current threats)</i></p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_03012(),
        ];
    }
}