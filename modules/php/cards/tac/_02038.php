<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02038;

class _02038 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Gold or Steel');
        $this->Image = '02038.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 38;

        $this->initializeFaction('Castille');

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 2;

        $this->WealthCost = 0;

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Crime'),
            clienttranslate('Theft'),
        ];

        $this->Text = clienttranslate("<p><b>Pirate Maneuver:</b> If your participant has more [Combat] than the adversary • Draw a card unless the adversary suffers a wound.</p><p><b>Duelist Maneuver:</b> If your participant has more [Finesse] than the adversary • Draw a card unless the adversary suffers a wound.</p>");

        $this->resetCard();

        $pirateManeuver = new Maneuver_02038("Pirate", "Combat", clienttranslate("Draw or Wound (Pirate)"));
        $pirateManeuver->setId("Maneuver_02038_Pirate");

        $duelistManeuver = new Maneuver_02038("Duelist", "Finesse", clienttranslate("Draw or Wound (Duelist)"));
        $duelistManeuver->setId("Maneuver_02038_Duelist");

        $this->Maneuvers = [
            $pirateManeuver,
            $duelistManeuver,
        ];
    }
}

