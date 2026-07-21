<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03067;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03067 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Ambitious");
        $this->Image = '03067.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 67;

        $this->initializeFaction('Neutral');
        
        $this->WealthCost = 1;

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate('Hubris'),
            clienttranslate('Unique')
        ];

        $this->Text = clienttranslate("<b>Leader City Action</b>: Wound your performer • If you control fewer locations than an opponent, pressure your performer's location with your choice of [Combat], [Finesse], or [Influence].
        <br>If successful, claim this location. Send this card to <b>The Locker</b>.");

        $this->resetCard();

        $this->Actions = [
            new Action_03067(),
        ];
    }
}
