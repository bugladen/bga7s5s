<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03cd18;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _03cd18 extends CityCharacter implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Kalla and Adelheide');
        $this->Title = clienttranslate('Hammer Meets Anvil');
        $this->Image = '03cd18.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 0;
        $this->CityCardNumber = 18;

        $this->WealthCost = 5;
        $this->Resolve = 4;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 0;
        $this->DashedInfluence = true;
        $this->Negotiable = true;

        $this->Traits = [
            clienttranslate('Mercenary'),
            clienttranslate('Academic'),
            clienttranslate('Artisan'),
            clienttranslate('Eisen'),
            clienttranslate('Vesten')
        ];

        $this->Text = clienttranslate("<p><b>Negotiable:</b> Parley is allowed when paying.</p><p><b>Reaction:</b> After you recruit Kalla and Adelheide • Choose one: <i>Either</i> search your deck for an attachment, reveal it, and add it to your hand, <i>or</i> move them to any location and destroy target attachment equipped to an opposing character. <i>(Shuffle your deck after searching.)</i></p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_03cd18(),
        ];
    }
}
