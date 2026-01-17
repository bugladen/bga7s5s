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
        $this->Title = 'Femina Calamita';
        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 1;

        $this->Traits = [
            'Duelist',
            'Sorcerer',
            'Strega',
            'Vodacce',
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_02001(),
        ];

        $this->Reactions = [
            new Reaction_02001(),
        ];
    }
}