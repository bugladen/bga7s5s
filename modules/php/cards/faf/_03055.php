<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _03055 extends FactionAttachment implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Syrneth Compass");
        $this->Title = clienttranslate("Recovered Relic");
        $this->Image = '03055.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 55;

        $this->initializeFaction('Ussura');

        $this->WealthCost = 0;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Riposte = 3;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->Traits = [
            clienttranslate('Artifact'),
            clienttranslate('Syrneth'),
            clienttranslate('Unique')
        ];

        $this->Text = clienttranslate("<b>City Action:</b> Engage this card • Move the equipped character to a location where there is a <b>Scion</b> or an <b>Artifact</b>. <i>(The Artifact may be available or equipped.)</i>");

        $this->resetCard();

        $this->Actions = [
            new Action_03055(),
        ];
    }
}
