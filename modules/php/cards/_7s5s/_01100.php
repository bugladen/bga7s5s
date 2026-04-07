<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01100;

class _01100 extends FactionAttachment implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("The Cat's Glass");
        $this->Image = "01100.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction("Castille");
        
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 1;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 2;
        $this->Riposte = 2;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate('Trinket'),
            clienttranslate('Unique'),
        ];

        $this->Text = clienttranslate("<p><b>Reaction:</b> When a character at this location accepts a challenge, engage this card • For the rest of the duel, while the adversary is at this location, they reveal one less card when gambling.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01100(),
        ];
    }
}