<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03cd10;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _03cd10 extends CityCharacter implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate('Julius Caligari');
        $this->Image = '03cd10.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 0;
        $this->Title = clienttranslate('Scion, Survivor');

        $this->CityCardNumber = 10;

        $this->WealthCost = 4;

        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 3;
        $this->Influence = 2;
        $this->Negotiable = true;

        $this->Traits = [
            clienttranslate('Mercenary'),
            clienttranslate('Villain'),
            clienttranslate('Spy'),
            clienttranslate('Vodacce')
        ];

        $this->Text = clienttranslate("<p><b>Negotiable:</b> Parley is allowed when paying.</p><p><b>Reaction:</b> After Julius is recruited or moves to a <b>City</b> location • Name a Trait and target an opposing character. Then, reveal two cards at random from the hand of that character's controller. If any of the revealed cards has the named <b>Trait</b>, wound that character.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_03cd10(),
        ];
    }
}