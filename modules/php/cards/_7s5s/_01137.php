<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01137;

class _01137 extends Risk implements IHasReactions
{
    use ReactionTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Predatory Pursuit");
        $this->Image = "01137.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction("Ussura");

        $this->WealthCost = 1;
        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 1;
        $this->Thrust = 4;

        $this->Traits = [
            'Hunt',
            'Relentless',
        ];

        $this->Text = "<p>City Reaction: After an enemy character moved from this location to a different City location • Move your performer from this location to their new one and wound the enemy character.</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01137(),
        ];
    }
}