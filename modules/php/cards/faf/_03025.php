<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03025;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03025a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03025b;

class _03025 extends Leader implements IHasReactions, IHasTechniques
{
    use ReactionTrait;
    use TechniqueTrait;
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Angeline Dèmone');
        $this->Title = clienttranslate('Prodigal Capitaine');
        $this->Image = '03025.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 25;

        $this->InPlayXImageOffset = -10;
        $this->initializeFaction('Montaigne');

        $this->Resolve = 9;
        $this->Combat = 3;
        $this->Finesse = 3;
        $this->Influence = 1;

        $this->CrewCap = 5;
        $this->Panache = 6;

        $this->Traits = [
            clienttranslate('Leader'),
            clienttranslate('Villain'),
            clienttranslate('Duelist'),
            clienttranslate('Pirate'),
            clienttranslate('Sorcerer'),
            clienttranslate('Montaigne')
        ];

        $this->Text = clienttranslate("<p>After Angeline moves to a <b>City</b> location, wound an engaged character opposing her.</p>
        <p><b>Technique:</b> +1 [Riposte]</p>
        <p><b>Gambling Technique:</b> Move both participants to any <b>City</b> location. <br /> <i>(The duel continues)</i></p>");

        $this->resetCard();

        $this->Reactions = [ new Reaction_03025() ];
        $this->Techniques = [
            new Technique_03025a(),
            new Technique_03025b(),
        ];
    }
}

