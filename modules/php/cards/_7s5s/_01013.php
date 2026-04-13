<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;

class _01013 extends Character implements IHasTechniques, IHasReactions
{
    use TechniqueTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Vissenta Scarpa";
        $this->Image = "01013.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 13;

        $this->initializeFaction("Vodacce");
        $this->Title = clienttranslate("Mouse Among Rats");
        $this->Resolve = 4;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->Traits = [
            clienttranslate("Hero"),
            clienttranslate("Duelist"),
            clienttranslate("Vodacce"),
        ];

        $this->Text = clienttranslate("<p><b>Reaction:</b> After your Red Hand is destroyed at this location • Draw a card.</p><p><b>Technique:</b> If Vissenta has equal or more wounds than the adversary • [+1 Parry] or [+1 Thrust].</p>");

        $this->resetCard();

        $this->Techniques = [
            new Technique_01013(),
        ];

        $this->Reactions = [
            new Reaction_01013(),
        ];
    }
}