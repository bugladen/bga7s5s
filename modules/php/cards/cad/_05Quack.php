<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Quack;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Quack;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _05Quack extends Character implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Takama Siad";
        $this->Title = "Heartless Healer";
        $this->Image = "05Quack.v5.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 24;

        $this->initializeFaction("Castille");

        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->Traits = [
            "Academic",
            "Scoundrel",
            "Surgeon",
            "Maghreb"
        ];

        $this->Text = "<p><b>Action:</b> Target character at this location heals a wound • Their controler discards a card.</p>
<p><b>Covert Reaction:</b> When a you discard a <b>Discovery</b> or <b>Alquimia</b> • Wound target opposing character.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_05Quack(),
        ];
        $this->Reactions = [
            new Reaction_05Quack(),
        ];
    }
}
