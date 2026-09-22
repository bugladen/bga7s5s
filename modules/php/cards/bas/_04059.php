<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04059;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _04059 extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Silver Tongue');
        $this->Image = '04059.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 59;

        $this->initializeFaction('Neutral');

        $this->WealthCost = 1;

        $this->Riposte = 1;
        $this->Parry = 2;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->Traits = [
            clienttranslate('Cunning'),
            clienttranslate('Savvy')
        ];

        $this->Text = clienttranslate("<p>While your performer is a <b>Merchant</b> or <b>Scoundrel</b>, this card has -1 cost.</p>
<p><b>En Garde Action:</b> If your performer is a non-<b>Hero</b> • They recruit target available <b>Mercenary</b> at their location, paying all costs. They may parley without engaging.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04059(),
        ];
    }
}
