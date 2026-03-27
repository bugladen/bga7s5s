<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01025;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01025 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Fate's Burden";
        $this->Image = '01025v2.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 25;

        $this->initializeFaction('Vodacce');

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->WealthCost = 0;

        $this->Traits = [
            clienttranslate('Sorcery'),
            clienttranslate('Sorte'),
        ];

        $this->Text = clienttranslate("<p>Forced: At the end of High Drama, if this card is equipped • Destroy it.</p><p>Sorcerer Strega Action: Equip this card to an opposing character. This ability cannot be copied.</p><p>The equipped character gains:</p><p>\"Forced: When this character would en garde • Destroy this card instead.\"</p>");
 
        $this->resetCard();

        $this->Actions = [
            new Action_01025(),
        ];
   }
}