<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02042;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _02042 extends Character implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();
        
        
        $this->Name = clienttranslate('Ivy');
        $this->Image = '02042.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 42;

        $this->Title = clienttranslate('Rune Breaker');
        $this->initializeFaction('Ussura');

        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 1;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate('Sorcerer'),
            clienttranslate('Vala'),
            clienttranslate('Pirate'),
            clienttranslate('Vesten'),
        ];

        $this->Text = clienttranslate("<p>When your participant at this location gambles, they reveal an additional card.</p><p><b>Sorcerer City Reaction:</b> After your participant at this location reveals their gambled cards • Put a revealed <b>Sorcery</b> into your hand. <i>(Before choosing one as a combat card.)</i>");


        $this->resetCard();

        $this->Reactions = [
            new Reaction_02042(),
        ];
    }

    public function getNumberOfGambleCardsToReveal(Theah $theah, Character $actor, array &$explanations): int
    {
        $count = parent::getNumberOfGambleCardsToReveal($theah, $actor, $explanations);

        if ($actor->ControllerId == $this->ControllerId
            && $theah->cardInCity($this)
            && $actor->Location == $this->Location)
        {
            $count += 1;
            $explanations[] = sprintf($theah->game->translate("%s: +1 card when your participant at this location gambles."), $this->getInjectCode());
        }

        return $count;
    }
}