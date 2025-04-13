<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class Attachment extends Card implements IWealthCost
{
    use WealthCostTrait;

    public int $ResolveModifier;
    public int $CombatModifier;
    public int $FinesseModifier;
    public int $InfluenceModifier;

    public int $AttachedToId;
    
    public function __construct()
    {
        parent::__construct();

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;
        $this->AttachedToId = 0;
    }

    public function getEquipDiscount(Theah $theah, Character $performer, Attachment $attachment): int
    {
        return 0;
    }

    public function getReactionFromHandDiscount(Theah $theah, CardReaction $reaction): int
    {
        return parent::getReactionFromHandDiscount($theah, $reaction);
    }


    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();
        
        $properties['resolveModifier'] = $this->ResolveModifier;
        $properties['combatModifier'] = $this->CombatModifier;
        $properties['finesseModifier'] = $this->FinesseModifier;
        $properties['influenceModifier'] = $this->InfluenceModifier;

        $properties['type'] = 'Attachment';
        $properties['attachedToId'] = $this->AttachedToId;

        return $properties;
    }

    public function isAttached(): bool
    {
        return $this->AttachedToId > 0;
    }

}
