<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01206;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01206 extends CityAttachment implements IHasActions
{
    use ActionTrait;
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Captain's Coat");
        $this->Image = "01206.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 206;
        
        $this->CityCardNumber = 30;
        $this->WealthCost = 3;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Traits = [
            'Attire',
            'Coat',
        ];

        $this->Text = clienttranslate("<p>City Action: Engage this card • Pressure the performer's location with [Influence]. If successful, claim this location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01206(),
        ];
    }
}
