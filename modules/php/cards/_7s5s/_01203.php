<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01203;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _01203 extends CityCharacter implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Leja Juska");
        $this->Image = "01203.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 203;

        $this->Title = clienttranslate('Whispered Shade');

        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 1;

        $this->WealthCost = 5;
        $this->CityCardNumber = 27;
        $this->Negotiable = true;

        $this->Traits = [
            clienttranslate('Mercenary'),
            clienttranslate('Duelist'),
            clienttranslate('Sarmatian'),
        ];

        $this->Text = clienttranslate("<p>Negotiable (You may parley when paying for this card.)</p><p><b>Reaction:</b> When a duel occurs at Leja's location, at the beginning of the first round • Add or remove a threat from one of the participants.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01203(),
        ];
    }
}