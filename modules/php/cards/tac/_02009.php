<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02009;

class _02009 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Murder");
        $this->Image = "02009.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 9;

        $this->initializeFaction("Vodacce");

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->WealthCost = 0;

        $this->Traits = [
            "Flourish",
            "Villainous",
            "Crime"
        ];

        $this->Text = clienttranslate("<p><b>Thug Maneuver:</b> Wound the adversary.</p><p><b>Duelist Maneuver:</b> Wound the adversary.</p><p><b>Spy Maneuver:</b> Wound the adversary.</p>");

        $this->resetCard();

        $thugManeuver = new Maneuver_02009("Thug", clienttranslate("Wound Adversary (Thug)"));
        $thugManeuver->setId("Maneuver_02009_Thug");

        $duelistManeuver = new Maneuver_02009("Duelist", clienttranslate("Wound Adversary (Duelist)"));
        $duelistManeuver->setId("Maneuver_02009_Duelist");

        $spyManeuver = new Maneuver_02009("Spy", clienttranslate("Wound Adversary (Spy)"));
        $spyManeuver->setId("Maneuver_02009_Spy");

        $this->Maneuvers = [
            $thugManeuver,
            $duelistManeuver,
            $spyManeuver,
        ];
    }
}