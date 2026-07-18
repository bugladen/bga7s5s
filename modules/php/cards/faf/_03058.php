<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03058 extends Risk implements IHasActions, IHasManeuvers, IRiskThatTargetsCharacters
{
    use ActionTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Courageous");
        $this->Image = '03058.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 58;

        $this->initializeFaction('Ussura');

        $this->WealthCost = 1;

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate('Virtue'),
            clienttranslate('Challenge'),
            clienttranslate('Heroic')
        ];

        $this->Text = clienttranslate("<p><b>Duelist City Action:</b> Target an opposing character • If their controller has more characters at this location than you, your performer issues a [Combat] challenge to that character.</p>
<p><b>Gambling Maneuver:</b> +1[Parry] and +1[Thrust] for each opposing character.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03058(),
        ];

        $this->Maneuvers = [
            new Maneuver_03058(),
        ];
    }
}
