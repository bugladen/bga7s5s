<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class Attachment extends Card implements IWealthCost
{
    use WealthCostTrait;

    public string $Title = "";

    public int $ResolveModifier;
    public int $CombatModifier;
    public int $FinesseModifier;
    public int $InfluenceModifier;

    public bool $OffHand = false;

    public bool $ResolveLocked = false;
    public bool $CombatLocked = false;
    public bool $FinesseLocked = false;
    public bool $InfluenceLocked = false;

    public int $ResolveLockedValue = 0;
    public int $CombatLockedValue = 0;
    public int $FinesseLockedValue = 0;
    public int $InfluenceLockedValue = 0;

    public int $AttachedToId;

    public bool $ShowStatModifiers = true;
    public bool $FakeAttachment = false;
    
    public function __construct()
    {
        parent::__construct();

        $this->Title = "";

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;
        $this->AttachedToId = 0;
        $this->ShowStatModifiers = true;
        $this->FakeAttachment = false;
    }

    public function getPropertyArray(Game $game): array
    {
        $properties = parent::getPropertyArray($game);
        
        $properties['title'] = $this->Title;
        $properties['resolveModifier'] = $this->ResolveModifier;
        $properties['combatModifier'] = $this->CombatModifier;
        $properties['finesseModifier'] = $this->FinesseModifier;
        $properties['influenceModifier'] = $this->InfluenceModifier;
        $properties['offHand'] = $this->OffHand;

        $properties['type'] = 'Attachment';
        $properties['attachedToId'] = $this->AttachedToId;
        $properties['showStatModifiers'] = $this->ShowStatModifiers;
        $properties['fakeAttachment'] = $this->FakeAttachment;

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

    public function canAttachTo(Character $character): bool
    {
        return true;
    }

    // WHY: Default true. Kaspar's Panzerhand (_01047) overrides when printed text
    // blocks moving while equipped. Take-control steal flows (unequip → hand → equip)
    // filter with this; discard-pile retrieval is not "moving" an equipped attachment.
    public function canBeMoved(): bool
    {
        return true;
    }
    
}
