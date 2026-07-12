<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03038a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03038b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _03038 extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Damya Kahina');
        $this->Title = clienttranslate('Sea Serpent');
        $this->Image = '03038.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 38;

        $this->initializeFaction('Castille');

        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 3;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate('Scoundrel'),
            clienttranslate('Pirate'),
            clienttranslate('Fence'),
            clienttranslate('Numa')
        ];

        $this->Text = clienttranslate("<p><b>City Action:</b> Draw a card. Then, discard a card.</p>
<p><b>City Action:</b> Your equipped character moves to this location. Then, destroy their attachment to draw cards equal to its printed cost, plus one. <i>(The attachment must be destroyed to draw cards)</i></p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03038a(),
            new Action_03038b(),
        ];
    }
}
