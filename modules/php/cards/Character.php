<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class Character extends Card
{
    public string $Title;
    public int $Resolve;
    public int $ModifiedResolve;
    public int $Wounds;
    public int $Combat;
    public int $ModifiedCombat;
    public int $Finesse;
    public int $ModifiedFinesse;
    public int $Influence;
    public int $ModifiedInfluence;

    public int $ModifiedEquipDiscount;

    public Array $Attachments = [];

    public function __construct()
    {
        parent::__construct();

        $this->Resolve = 0;
        $this->ModifiedResolve = 0;
        $this->Wounds = 0;
        $this->Combat = 0;
        $this->ModifiedCombat = 0;
        $this->Finesse = 0;
        $this->ModifiedFinesse = 0;
        $this->Influence = 0;
        $this->ModifiedInfluence = 0;
        $this->ModifiedEquipDiscount = 0;
    }

    public function resetModifiedCharacterStats()
    {
        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;
    }

    public function hasWhenRevealedEffect() : bool
    {
        return false;
    }

    public function getParleyDiscount(bool $parleying) : int
    {
        return $parleying ? $this->ModifiedInfluence : 0;
    }

    public function getEquipDiscount() : int
    {
        return $this->ModifiedEquipDiscount;
    }

    public function addAttachment(Attachment $attachment)
    {
        $this->ModifiedResolve += $attachment->ResolveModifier;
        $this->ModifiedCombat += $attachment->CombatModifier;
        $this->ModifiedFinesse += $attachment->FinesseModifier;
        $this->ModifiedInfluence += $attachment->InfluenceModifier;

        $this->Attachments[] = $attachment->Id;
    }

    public function removeAttachment(Attachment $attachment)
    {
        $index = array_search($attachment->Id, $this->Attachments);
        if ($index !== false) {
            $this->ModifiedResolve -= $attachment->ResolveModifier;
            $this->ModifiedCombat -= $attachment->CombatModifier;
            $this->ModifiedFinesse -= $attachment->FinesseModifier;
            $this->ModifiedInfluence -= $attachment->InfluenceModifier;

            unset($this->Attachments[$index]);
        }    
    }

    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();

        //Add character specific properties
        $properties['title'] = $this->Title;
        $properties['resolve'] = $this->Resolve;
        $properties['modifiedResolve'] = $this->ModifiedResolve;
        $properties['wounds'] = $this->Wounds;
        $properties['combat'] = $this->Combat;
        $properties['modifiedCombat'] = $this->ModifiedCombat;
        $properties['finesse'] = $this->Finesse;
        $properties['modifiedFinesse'] = $this->ModifiedFinesse;
        $properties['influence'] = $this->Influence;
        $properties['modifiedInfluence'] = $this->ModifiedInfluence;
        $properties['modifiedEquipDiscount'] = $this->ModifiedEquipDiscount;

        $properties['attachments'] = $this->Attachments;

        $properties['type'] = 'Character';

        return $properties;
    }
}