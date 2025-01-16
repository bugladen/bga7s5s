<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

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

    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();
        $this->addWealthCostProperties($properties);
        
        $properties['resolveModifier'] = $this->ResolveModifier;
        $properties['combatModifier'] = $this->CombatModifier;
        $properties['finesseModifier'] = $this->FinesseModifier;
        $properties['influenceModifier'] = $this->InfluenceModifier;

        $properties['type'] = 'Attachment';
        $properties['attachedToId'] = $this->AttachedToId;

        return $properties;
    }

}
