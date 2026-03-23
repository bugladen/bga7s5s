<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01171;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01171 extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Paid Off");
        $this->Image = "01171.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->WealthCost = 2;
        $this->Riposte = 1;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 2;

        $this->Traits = [
            'Cunning',
            'Villainous',
        ];

        $this->Text = "<p>While your performer is a Villain or Scoundrel, this card has -1 cost.</p><p>City Action. Engage target Mercenary opposing your performer. If they are already engaged, move them Home instead.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_01171(),
        ];
    }
}