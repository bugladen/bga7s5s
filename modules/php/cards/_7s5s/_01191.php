<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01191;

class _01191 extends CityAttachment implements IHasActions
{
    use ActionTrait;
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Duckfoot Pistol');
        $this->Image = "01191.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 191;
        
        $this->CityCardNumber = 15;
        $this->WealthCost = 1;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Traits = [
            clienttranslate('Weapon'),
            clienttranslate('Ranged'),
            clienttranslate('Pistol'),
        ];

        $this->Text = clienttranslate("<p><b>Action:</b> Destroy this card • Wound all non-Leader characters at this location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01191(),
        ];
    }
}