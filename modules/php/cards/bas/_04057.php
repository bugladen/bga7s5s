<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04057;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _04057 extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Honorable');
        $this->Image = '04057.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 57;

        $this->initializeFaction('Neutral');

        $this->WealthCost = 1;
        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate('Virtue'),
            clienttranslate('Challenge'),
            clienttranslate('Unique')
        ];

        $this->Text = clienttranslate("<p><b>En Garde Leader Action:</b> If you control fewer characters than an opponent • Your performer issues a challenge to target opposing non-<b>Leader</b> controlled by that opponent, using your choice of [Combat], [Finesse], or [Influence].</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04057(),
        ];
    }
}
