<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
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

    public function getActionFromHandDiscount(Theah $theah, Character $performer): int
    {
        return 0;
    }

    public function getEquipDiscount(Theah $theah, Character $performer, Attachment $attachment): int
    {
        $discount = parent::getEquipDiscount($theah, $performer, $attachment);
        return $discount;
    }

    public function getReactionFromHandDiscount(Theah $theah, CardReaction $reaction): int
    {
        return parent::getReactionFromHandDiscount($theah, $reaction);
    }


    public function getPropertyArray(Game $game): array
    {
        $properties = parent::getPropertyArray($game);
        
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

    public function attachedTo(Theah $theah): ?Card
    {
        if (!$this->isAttached())
            return null;
        
        return $theah->getCardById($this->AttachedToId);
    }

    public function getRequiredAttachTargetId(Theah $theah, int $originalTargetId): int
    {
        return $originalTargetId;
    }

}
