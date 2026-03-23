<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01010;

class _01010 extends Character implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();
        
        $this->Name = clienttranslate("Sarafina");
        $this->Image = "01010.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 10;

        $this->initializeFaction("Vodacce");
        $this->Title = "Ice Heart";
        $this->Resolve = 5;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->Traits = [
            "Spy",
            "Vodacce",
        ];

        $this->Text = "<p>When Sarafina gambles, reveal an additional card.</p><p>Technique: Look at the top card of the adversary's deck, or two cards instead if you control a Strega at this location. Sink any number of them.</p>";

        $this->resetCard();

        $this->Techniques = [
            new Technique_01010(),
        ];
    }

    public function getNumberOfGambleCardsToReveal(Theah $theah, Character $actor, array &$explanations): int
    {
        $count = parent::getNumberOfGambleCardsToReveal($theah, $actor, $explanations);

        if ($actor->Id == $this->Id)
        {
            $count += 1;
            $explanations[] = sprintf($theah->game->translate("%s reveals +1 card when Gambling."), $this->getInjectCode());
        }

        return $count;
    }
}

