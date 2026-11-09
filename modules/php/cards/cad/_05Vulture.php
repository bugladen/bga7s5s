<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Vulture;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Vulture;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _05Vulture extends Character implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Gusev Grabiezca";
        $this->Title = "Garbage Picker";
        $this->Image = "05Vulture.v5.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 23;

        $this->initializeFaction("Ussura");

        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 1;
        $this->Influence = 2;

        $this->Traits = [
            "Villain",
            "Merchant",
            "Academic",
            "Ussura"
        ];

        $this->Text = "<p><b>Covert Action:</b> Wound target opposing <b>Scion</b> or equipped character.</p>
<p><b>Reaction:</b> When a character at this location is destroyed • Take control of their equipped attachment and equip it to a character you control paying all costs.</p>";
        $this->resetCard();

        $this->Actions = [
            new Action_05Vulture(),
        ];

        $this->Reactions = [
            new Reaction_05Vulture(),
        ];
    }
}
