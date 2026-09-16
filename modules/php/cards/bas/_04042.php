<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04042;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04042;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _04042 extends Character implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Kaj Kousei');
        $this->Title = clienttranslate('Relic Raider');
        $this->Image = '04042.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 42;

        $this->initializeFaction("Ussura");
        $this->InPlayXImageOffset = 10;

        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate("Academic"),
            clienttranslate("Explorer"),
            clienttranslate("Numa")
        ];

        $this->Text = clienttranslate("<p><b>Reaction:</b> After Kaj musters • Search the City Deck for an <b>Artifact</b> and equip it to your character at <b>Home</b>, paying all costs. <i>(Shuffle the City Deck.)</i></p>
<p><b>En Garde Action:</b> If you control an <b>Artifact</b> at this location • Move target opposing engaged character to a <b>City</b> location you do not control.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04042(),
        ];
        $this->Reactions = [
            new Reaction_04042(),
        ];
    }
}
