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
        $this->Title = clienttranslate('Fortune Weaver');
        $this->Resolve = 3;
        $this->Combat = 1;
        $this->Finesse = 3;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate('Sorcerer'),
            clienttranslate('Strega'),
            clienttranslate('Red Hand'),
            clienttranslate('Vodacce'),
        ];

        $this->Text = clienttranslate("<p><b>Sorcerer City Action:</b> Look at the top three cards of target player's deck, put one or more of them into the discard pile, then replace the rest in any order.</p><p><b>Sorcerer Technique:</b> -1[Thrust].</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_02002(),
        ];

        $this->Techniques = [
            new Technique_02002(),
        ];
    }
}