<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01070;

class _01070 extends Character implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Urraca de Murrieta");
        $this->Image = "01070.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 70;

        $this->initializeFaction("Montaigne");
        $this->Title = clienttranslate("Madamoiselle Exemplar");
        $this->Resolve = 5;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate("Dimplomat"),
            clienttranslate("Castille"),
        ];

        $this->Text = clienttranslate("<p>Reaction: After you add a Renown to a location, discard a card • Add another Renown to that location. (Moving Renown is not adding Renown.)</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01070(),
        ];
    }
    
}