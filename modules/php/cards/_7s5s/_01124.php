<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01124;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01124;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _01124 extends Character implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Ved'ma");
        $this->Image = "01124.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 124;

        $this->initializeFaction("Ussura");
        $this->Title = clienttranslate("Ancient Witch");
        $this->Resolve = 3;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 3;

        $this->Traits = [
            clienttranslate("Sorcerer"),
            clienttranslate("Ussura"),
        ];

        $this->Text = clienttranslate("<p>Sorcerer Action: Engage Ved'ma • Play target Sorcery in your discard pile as if it was in your hand. After it resolves, send it to The Locker.</p><p>Reaction: After you play a Sorcery from your hand that engaged Ved'ma • En garde her.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01124(),
        ];

        $this->Reactions = [
            new Reaction_01124(),
        ];
    }

}