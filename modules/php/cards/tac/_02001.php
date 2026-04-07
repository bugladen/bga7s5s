<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02001;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02001;

class _02001 extends Character implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Andriana Dondolo');
        $this->Image = '02001.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 1;

        $this->initializeFaction('Vodacce');
        $this->Title = clienttranslate('Femina Calamita');
        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 1;

        $this->Traits = [
            clienttranslate('Duelist'),
            clienttranslate('Sorcerer'),
            clienttranslate('Strega'),
            clienttranslate('Vodacce'),
        ];

        $this->Text = clienttranslate("<p><b>Sorcerer Reaction:</b> When target opposing non-<b>Sorcerer</b> intervenes or refuses a challenge • Wound them.</p><p><b>Sorcerer City Action:</b> Discard a <b>Sorcery</b> and engage Andriana • Move target enemy non-<b>Leader</b> at a City location to Andriana's location. Then, she issues them a [Combat] challenge.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_02001(),
        ];

        $this->Reactions = [
            new Reaction_02001(),
        ];
    }
}