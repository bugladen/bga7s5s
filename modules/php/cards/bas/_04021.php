<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04021;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04021a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04021b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _04021 extends Character implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Aimée Barrere");
        $this->Title = clienttranslate("Steadfast and True");
        $this->Image = "04021.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 21;

        $this->initializeFaction("Montaigne");

        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate("Hero"),
            clienttranslate("Duelist"),
            clienttranslate("Musketeer"),
            clienttranslate("Montaigne")
        ];

        $this->Text = clienttranslate("<p>After an opponent's effect engages your other <b>Musketeer</b> at this location, they may en garde.</p>
<p><b>Technique:</b> Copy the effects of a <b>Technique</b> on your other <b>Musketeer</b> at this location. <i>(Not one of their attachments.)</i></p>
<p><b>En Garde Technique:</b> +1[Thrust]</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_04021(),
        ];

        $this->Techniques = [
            new Technique_04021a(),
            new Technique_04021b(),
        ];
    }
}
