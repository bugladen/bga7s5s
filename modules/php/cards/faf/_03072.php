<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03072;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03072;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03072 extends Risk implements IHasActions, IHasManeuvers, IRiskThatTargetsCharacters
{
    use ActionTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Sabotage");
        $this->Image = '03072.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 72;

        $this->initializeFaction('Neutral');

        $this->WealthCost = 0;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 5;

        $this->Traits = [
            clienttranslate("Flourish"),
            clienttranslate("Demoralize"),
            clienttranslate("Vandalize")
        ];

        $this->Text = clienttranslate("<p><b>City Action:</b> Destroy all engaged attachments equipped to target opposing character. Then, engage each of their equipped attachments.</p>
<p><b>Maneuver:</b> Destroy all engaged attachments equipped to the adversary.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03072(),
        ];

        $this->Maneuvers = [
            new Maneuver_03072(),
        ];
    }
}
