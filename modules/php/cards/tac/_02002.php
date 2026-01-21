<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02002;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02002;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;

class _02002 extends Character implements IHasActions, IHasTechniques
{
    use ActionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Elisabetta Bonora');
        $this->Image = '02002.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 2;

        $this->initializeFaction('Vodacce');
        $this->Title = 'Fortune Weaver';
        $this->Resolve = 3;
        $this->Combat = 1;
        $this->Finesse = 3;
        $this->Influence = 2;

        $this->Traits = [
            'Sorcerer',
            'Strega',
            'Red Hand',
            'Vodacce',
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_02002(),
        ];

        $this->Techniques = [
            new Technique_02002(),
        ];
    }
}