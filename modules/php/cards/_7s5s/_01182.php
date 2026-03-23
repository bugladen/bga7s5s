<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01182;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _01182 extends CityCharacter implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Eko Sorridi");
        $this->Image = "01182.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 182;

        $this->Title = 'Short-Tempered Gambler';

        $this->Resolve = 3;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->WealthCost = 4;        
        $this->CityCardNumber = 6;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Pirate',
            'Maghreb',
        ];

        $this->Text = clienttranslate("<p>Negotiable (You may parley when paying for this card.)</p><p>City Reaction: Before an opposing character moves from this location • Wound them. (Even during Dusk.)</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01182()
        ];
    }
}

