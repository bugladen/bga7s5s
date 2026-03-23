<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02012;

class _02012 extends Character implements IHasReactions, IHasTechniques
{
    use ReactionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Turais Dall');
        $this->Image = '02012.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 12;

        $this->initializeFaction('Eisen');
        $this->Title = 'The Curaidh';
        $this->Resolve = 4;
        $this->Combat = 3;
        $this->Finesse = 1;
        $this->Influence = 0;

        $this->Traits = [
            'Hero',
            'Berserker',
            'Highland Marches'
        ];

        $this->Text = "<p><b>Berserker Reaction:</b> When Turais issues a challenge, wound him • En garde him.</p><p><b>Berserker Technique:</b> Wound Turais • Remove all threat from him.</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_02012(),
        ];

        $this->Techniques = [
            new Technique_02012(),
        ];
    }
}