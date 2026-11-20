<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Terraria;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _05Terraria extends FactionAttachment implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Cloud Bottle";
        $this->Image = "05Terraria.v5.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 25;

        $this->initializeFaction("Castille");

        $this->WealthCost = 1;
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 1;

        $this->Traits = [
            "Alquimia",
            "Invention"
        ];

        $this->Text = "<p>This card has -1 cost while you control an <b>Academic</b>.</p>
<p><b>Covert Reaction:</b> When the equipped character is targeted, destroy this card • Cancel the effects.</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_05Terraria(),
        ];
    }

    public function getEquipDiscount(Theah $theah, Character $performer, Attachment $attachment, array &$explanations): int
    {
        $discount = parent::getEquipDiscount($theah, $performer, $attachment, $explanations);

        // WHY: Self-discount only while THIS card is being paid for. Character::getEquipDiscount
        // shadows $attachment with each equipped attachment — without !isAttached(), an already
        // equipped Cloud Bottle would +1 every subsequent equip on that host.
        if ($attachment->Id != $this->Id || $this->isAttached())
        {
            return $discount;
        }

        if ($performer->ControllerId != $this->ControllerId && $this->ControllerId != 0)
        {
            return $discount;
        }

        $playerId = $this->ControllerId > 0 ? $this->ControllerId : $performer->ControllerId;
        foreach ($theah->getCharactersInPlayByPlayerId($playerId) as $character)
        {
            if ($character->hasTrait('Academic'))
            {
                $discount += 1;
                // WHY: Prototype CAD — plain strings, no translate().
                $explanations[] = sprintf("%s: -1 cost while you control an Academic.", $this->getInjectCode());
                break;
            }
        }

        return $discount;
    }
}
