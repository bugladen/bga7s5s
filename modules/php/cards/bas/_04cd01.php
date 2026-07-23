<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04cd01;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04cd01b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _04cd01 extends CityAttachment implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Penya');
        $this->Title = clienttranslate('Lovable Scamp');
        $this->Image = '04cd01.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 0;

        $this->InPlayXImageOffset = 20;

        $this->CityCardNumber = 1;

        $this->WealthCost = 2;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Traits = [
            clienttranslate('Swindler'),
            clienttranslate('Guide'),
            clienttranslate('Unique')
        ];

        $this->Text = clienttranslate("<p><b>Action:</b> Engage this card • Move the equipped character to an adjacent City location.</p>
<p><b>City Action:</b> Sink this card • Play target risk from an opponent's discard pile, paying all costs. After it resolves, sink it.</p> ");

        $this->resetCard();

        $this->Actions = [
            new Action_04cd01(),
            new Action_04cd01b(),
        ];
    }
}
