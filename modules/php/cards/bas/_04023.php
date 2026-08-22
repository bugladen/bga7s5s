<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04023;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _04023 extends Character implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Monet Dantès");
        $this->Title = clienttranslate("Suave Salvager");
        $this->Image = "04023.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 23;

        $this->initializeFaction("Montaigne");

        $this->InPlayXImageOffset = -10;

        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->Traits = [
            clienttranslate("Duelist"),
            clienttranslate("Merchant"),
            clienttranslate("Pirate"),
            clienttranslate("Montaigne")
        ];

        $this->Text = clienttranslate("<p><b>En Garde Reaction:</b> After Monet moves to a <b>City</b> location • Reveal the top four cards of your deck. You may equip a revealed attachment to a character you control at this location, paying all costs. Discard any then sink the rest.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_04023(),
        ];
    }
}
