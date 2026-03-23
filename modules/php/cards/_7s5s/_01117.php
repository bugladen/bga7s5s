<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01117;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01117;

class _01117 extends Character implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();
        
        $this->Name = clienttranslate("Ekaterina Ilyanava");
        $this->Image = "01117.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 117;

        $this->initializeFaction("Ussura");
        $this->Title = "Eternal Scholar";
        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 3;

        $this->Traits = [
            "Academic",
            "Cartographer",
            "Ussura",
        ];

        $this->Text = "<p>City Action: Move a Renown from this location to another one • Move Ekaterina to another different City location.</p><p>Reaction: After an opponent claims this location • Remove a Renown from it.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_01117(),
        ];

        $this->Reactions = [
            new Reaction_01117(),
        ];
    }
}