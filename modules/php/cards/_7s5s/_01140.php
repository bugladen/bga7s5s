<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01140;

class _01140 extends Risk implements IHasReactions
{
    use ReactionTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Stubborn");
        $this->Image = "01140.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction("Ussura");

        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->Traits = [
            'Hubris',
        ];

        $this->Text = "<p>Reaction: Before your character moves • Cancel their movement. (Even during Dusk.)</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01140(),
        ];
    }
}