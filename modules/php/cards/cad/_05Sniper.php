<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Sniper;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques\Technique_05Sniper;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _05Sniper extends Character implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Frau Eleanor";
        $this->Title = "Inspiring Title Here";
        $this->Image = "05Sniper.v4.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 22;

        $this->initializeFaction("Eisen");

        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 1;

        $this->Traits = [
            "Hunter",
            "Höpken",
            "Eisen"
        ];

        $this->Text = "<p>While using your abilities, you may consider Eleanor to be equipped with a <b>Ranged Weapon</b>.</p>
<p><b>Reaction:</b> When a challenge is issued at this location or an adjacent one • Wound the challenger or the challenged character.</p>
<p><b>Technique:</b> Gain Lethal.</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_05Sniper(),
        ];

        $this->Techniques = [
            new Technique_05Sniper(),
        ];
    }

    public function countsAsEquippedWithRangedWeapon(Theah $theah, int $actingPlayerId = 0): bool
    {
        if (parent::countsAsEquippedWithRangedWeapon($theah, $actingPlayerId))
        {
            return true;
        }

        // WHY: The printed line is permission for the controller's own ability checks,
        // not a real attachment. Opponents still see only guns that are actually equipped.
        // Silence blanks the fiction; a real Ranged Weapon above still counts.
        // "May" is that permission — it satisfies "if equipped" gates. It does not
        // create a card that can be engaged or destroyed (Katain still needs a real one).
        if ($this->abilitiesAreBlanked())
        {
            return false;
        }

        if (! $this->isControlled() || $actingPlayerId !== $this->ControllerId)
        {
            return false;
        }

        if ($theah->game->characterIsInDiscardOrLocker($this))
        {
            return false;
        }

        return $theah->cardInPlay($this);
    }
}
