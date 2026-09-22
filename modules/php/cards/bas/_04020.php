<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04020;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _04020 extends Risk implements IHasReactions, IRiskThatTargetsCharacters
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Vantage Point");
        $this->Image = "04020.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 20;

        $this->initializeFaction("Eisen");

        $this->WealthCost = 1;

        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 4;

        $this->Traits = [
            clienttranslate("Ranged"),
            clienttranslate("Ambush")
        ];

        $this->Text = clienttranslate("<p><b>En Garde Reaction:</b> When a pressure occurs at an adjacent location, if your performer is equipped with a <b>Ranged Weapon</b> • Target opponent applies -1 to their total. Then, wound target character at that location unless they engage.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_04020(),
        ];
    }

}

