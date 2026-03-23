<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01199;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _01199 extends CityCharacter implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Takama Siad");
        $this->Image = "01199.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 199;

        $this->Title = 'Gilded Doctor';

        $this->Resolve = 3;
        $this->Combat = 0;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->WealthCost = 3;
        $this->CityCardNumber = 23;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Academic',
            'Surgeon',
            'Maghreb',
        ];

        $this->Text = clienttranslate("<p>Negotiable (You may parley when paying for this card.)</p><p>Reaction: After a duel, if your character that participated is at Takama's location • Heal a wound from them.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01199(),
        ];
    }
}