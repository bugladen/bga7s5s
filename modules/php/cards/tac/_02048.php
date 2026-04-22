<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02048;

class _02048 extends Risk implements IHasReactions
{
    use ReactionTrait;
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Blood Like Winter");
        $this->Image = "02048.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 48;

        $this->initializeFaction("Ussura");

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->WealthCost = 0;

        $this->Traits = [
            clienttranslate('Immovable'),
            clienttranslate('Relentless'),
        ];

        $this->Text = clienttranslate("<b>City Reaction:</b> When your opponent's risk targets your performer • Pressure your performer's location with [combat]. If successful, cancel the effects.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_02048(),
        ];
    }
}