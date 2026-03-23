<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01187;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01187 extends CityAttachment implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Smuggled Item');
        $this->Image = "01187.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 187;
        
        $this->CityCardNumber = 11;
        $this->WealthCost = 1;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 1;

        $this->Traits = [
            'Artifact',
        ];

        $this->Text = "<p>Action: Destroy this card • Equip an attachment from your hand or a City location to this character, ignoring all costs. (It must be available.)</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_01187(),
        ];
    }
}