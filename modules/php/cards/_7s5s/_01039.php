<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;

class _01039 extends Character implements IHasReactions, IHasTechniques
{
    use ReactionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Philip Hase");
        $this->Image = "01039.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 39;

        $this->initializeFaction("Eisen");
        $this->Title = "Grim Trapper";
        $this->Resolve = 5;
        $this->Combat = 3;
        $this->Finesse = 1;
        $this->Influence = 2;

        $this->Traits = [
            "Academic",
            "Hunter",
            "Eisen",
        ];

        $this->Text = clienttranslate("<p>City Reaction: After Philip equips an attachment • Move him to an adjacent location.</p><p>Technique: If the adversary is engaged and you control a Mercenary at this location • Wound the adversary.</p>");

        $this->resetCard();

        $this->Techniques = [
            new Technique_01039(),
        ];

        $this->Reactions = [
            new Reaction_01039(),
        ];
    }
}