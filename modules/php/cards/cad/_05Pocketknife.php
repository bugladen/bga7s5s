<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\maneuvers\Maneuver_05Pocketknife;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques\Technique_05Pocketknife;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;

class _05Pocketknife extends FactionAttachment implements IHasTechniques, IHasManeuvers
{
    use TechniqueTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "El Estoque Oculto";
        $this->Image = "05Pocketknife.v5.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        // WHY: Stub CardNumber=24 matches CAD sequence; state ids cannot be 52105024 —
        // Quack/Rum/etc. already collide on 24. Technique/Maneuver states use 52105242 / 52505242.
        $this->CardNumber = 24;

        $this->initializeFaction("Castille");

        // WHY: Art coin is Roman II — stub had WealthCost 0.
        $this->WealthCost = 0;
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Riposte = 0;
        $this->Parry = 3;
        $this->Thrust = 2;

        $this->Traits = [
            "Weapon",
            "Sword",
            "Melee",
            "Flourish",
            "Unique"
        ];

        $this->Text = "<p><b>Covert Technique:</b> Unequip this card and put it on top of your deck. The adversary discards a card.</p>
<p><b>Gambling Maneuver:</b> Wound the adversary. Put a card from your dueling line into your hand.</p>";

        $this->resetCard();

        $this->Techniques = [
            new Technique_05Pocketknife(),
        ];

        $this->Maneuvers = [
            new Maneuver_05Pocketknife(),
        ];
    }
}
