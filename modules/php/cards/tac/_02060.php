<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02060a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02060b;

class _02060 extends Risk implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Relentless');
        $this->Image = '02060.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 60;
        $this->initializeFaction('Neutral');

        $this->Riposte = 1;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 3;

        $this->WealthCost = 0;

        $this->Traits = [
            clienttranslate('Hubris')
        ];

        $this->Text = clienttranslate("<p><b>Reaction:</b> When your challenge is refused, wound your challenger • Wound the refusing character.</p><p><b>Reaction:</b> When the duel would end, wound your participant • Wound your opposing adversary.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_02060a(),
            new Reaction_02060b(),
        ];
    }
}