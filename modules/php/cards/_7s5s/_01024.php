<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01024;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01024 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Bravos');
        $this->Image = '01024.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 24;

        $this->initializeFaction('Vodacce');

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 3;

        $this->WealthCost = 1;

        $this->Traits = [
            clienttranslate('Conscription'),
            clienttranslate('Gang'),
        ];

        $this->Text = clienttranslate("<p>While paying for this card, Thugs cannot be spent.</p><p>Leader Action: Put target Thug from your discard pile into play at your performer's location, ignoring all costs.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01024(),
        ];
    }
}