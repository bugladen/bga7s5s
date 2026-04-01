<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01080;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01080 extends Risk implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Friends at Court");
        $this->Image = "01080.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 80;
        $this->initializeFaction("Montaigne");
        
        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->Traits = [
            clienttranslate('Bureaucracy'),
            clienttranslate('Rumor'),
        ];

        $this->Text = clienttranslate("<p>City Reaction: After a duel where your adversary was destroyed • Pressure your performer's location with [inf]. If successful, claim the location.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01080(),
        ];
    }
}