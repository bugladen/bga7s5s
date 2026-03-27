<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01097;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01097;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _01097 extends Character implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Sanjay");
        $this->Image = "01097.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 97;

        $this->initializeFaction("Castille");
        $this->Title = clienttranslate("Loyal Merrymaker");
        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 1;        
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate("Pirate"),
            clienttranslate("Aragosta"),
        ];

        $this->Text = clienttranslate("<p>City Action: Target an opposing character • If their controller has fewer cards in hand than you, engage them.</p><p>Reaction: After an opponent discards a card due to your effect • Draw a card.</p>");

        $this->Actions = [
            new Action_01097(),
        ];

        $this->Reactions = [
            new Reaction_01097(),
        ];

        $this->resetCard();
    }
}