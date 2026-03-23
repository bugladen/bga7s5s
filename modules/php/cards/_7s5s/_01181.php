<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01181;

class _01181 extends CityAttachment implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Sorte Deck');
        $this->Image = "01181.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 181;
        
        $this->CityCardNumber = 5;
        $this->WealthCost = 1;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Traits = [
            'Sorte',
            'Trinket',
        ];

        $this->Text = clienttranslate("<p>Reaction: After a character at this location is wounded, engage this card • Heal a wound from them, or two instead if the equipped character is a Strega.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01181()
        ];
    }
}

