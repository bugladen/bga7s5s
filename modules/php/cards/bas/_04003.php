<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04003a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04003b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _04003 extends Character implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Desideria Colomberia");
        $this->Title = clienttranslate("Merciful Matron");
        $this->Image = "04003.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 3;

        $this->initializeFaction("Vodacce");

        $this->Resolve = 5;
        $this->Combat = 1;
        $this->Finesse = 1;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate("Hero"),
            clienttranslate("Sorcerer"),
            clienttranslate("Strega"),
            clienttranslate("Vodacce")
        ];

        $this->Text = clienttranslate("<p><b>En Garde City Reaction:</b> After your <b>Thug</b> at this location is destroyed during a duel or by an opponent's effect, wound Desideria • Put the Thug in your hand.</p>
<p><b>City Reaction:</b> After Desideria performs a <b>Sorcerer</b> ability, wound her • Draw a card.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_04003a(),
            new Reaction_04003b(),
        ];
    }
}
