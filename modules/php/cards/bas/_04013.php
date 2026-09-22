<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _04013 extends Character implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Tomas E Spielen");
        $this->Title = clienttranslate("Restoration Hobbyist");
        $this->Image = "04013.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 13;

        $this->initializeFaction("Eisen");

        $this->Resolve = 5;
        $this->Combat = 2;
        $this->Finesse = 1;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate("Academic"),
            clienttranslate("Merchant"),
            clienttranslate("Antiquarian"),
            clienttranslate("Eisen")
        ];

        $this->Text = clienttranslate("<p><b>City Reaction:</b> When a non-<b>Artifact</b> attachment equipped to your character at this location would be put into a discard pile • Equip it to your character at this location instead, paying all costs.</p>
<p><b>Technique:</b> Destroy an attachment equipped to Tomas • +2[Thrust].</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_04013(),
        ];

        $this->Techniques = [
            new Technique_04013(),
        ];
    }
}
