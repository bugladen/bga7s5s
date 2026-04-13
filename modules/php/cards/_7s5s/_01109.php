<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01109;

class _01109 extends Risk implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Night of Drinking");
        $this->Image = "01109.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 109;
        $this->initializeFaction("Castille");
        
        $this->WealthCost = 1;
        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate('Revelry'),
            clienttranslate('Torpor'),
        ];

        $this->Text = clienttranslate("<p><b>Reaction:</b> When a non-Sorcery risk is announced • Cancel its effects. (All costs are still paid.)</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01109(),
        ];
    }
}