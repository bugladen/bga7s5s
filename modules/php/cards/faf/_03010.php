<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03010;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03010 extends Risk implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Manipulative");
        $this->Image = "03010.jpg";
        $this->ExpansionName = "faf";
        $this->ExpansionNumber = 3;
        $this->CardNumber = 10;

        $this->initializeFaction("Vodacce");

        $this->WealthCost = 0;

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate("Hubris"),
            clienttranslate("Sorcery"),
            clienttranslate("Sorte")
        ];

        $this->Text = clienttranslate("<p><b>Strega Reaction:</b> After target character is mustered from an Approach deck • Wound them unless their controller returns that character to their Approach deck and musters a different character. Wound them again if you control three or more <b>Strega</b>.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_03010(),
        ];
    }
}
