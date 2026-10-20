<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques\Technique_05Robin;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _05Robin extends FactionAttachment implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Rogue's Cowl";
        $this->Image = "05Robin.v4.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 16;

        $this->initializeFaction("Ussura");

        $this->WealthCost = 0;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 1;

        $this->Traits = [
            "Attire",
            "Stealth"
        ];

        $this->Text = "<p><i>Covert</i> - The equipped character reveals an additional card when they gamble.
<br>(<i>Covert Abilities may only be used at uncontrolled locations.</i>)</p>        
<p><b>Technique:</b> If your combat card is a Flourish or Stealth card • +1[Thrust].</p>";

        $this->resetCard();

        $this->Techniques = [
            new Technique_05Robin(),
        ];
    }

    public function getNumberOfGambleCardsToReveal(Theah $theah, Character $actor, array &$explanations): int
    {
        $count = parent::getNumberOfGambleCardsToReveal($theah, $actor, $explanations);

        if (! $this->isAttached() || $actor->Id != $this->AttachedToId)
        {
            return $count;
        }

        // WHY: Covert — only usable at an uncontrolled City location (printed reminder text).
        // Always-on gamble reveal otherwise (Gallegos / _04017 shape), so no duel-state gate.
        if (! $theah->cardInCity($actor))
        {
            return $count;
        }

        $cityLocation = $theah->getCityLocation($actor->Location);
        if ($cityLocation->isControlled())
        {
            return $count;
        }

        // WHY: Plain string, no translate() — prototype CAD card.
        $explanations[] = sprintf("%s (Covert): +1 for being attached to acting character at an uncontrolled location.", $this->getInjectCode());
        $count += 1;

        return $count;
    }
}
