<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01032;

class _01032 extends Risk implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Unyielding Loyalty");
        $this->Image = '01032.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 32;

        $this->initializeFaction('Vodacce');

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 1;

        $this->WealthCost = 1;

        $this->Traits = [
            "Camaraderie",
            "Zeal",
        ];

        $this->Text = clienttranslate("<p>Reaction: When one or more of your cards is targeted, destroy your Red Hand or discard a Thug from your hand • Cancel the effects.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01032(),
        ];
    }
}