<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class CityAttachment extends CityDeckCard
{
    public int $WealthCost;

    public int $ResolveModifier;
    public int $CombatModifier;
    public int $FinesseModifier;
    public int $InfluenceModifier;

    public function __construct()
    {
        parent::__construct();

        $this->WealthCost = 0;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;
    }

    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();

        //Add city attachment specific properties
        $properties['wealthCost'] = $this->WealthCost;

        $properties['resolveModifier'] = $this->ResolveModifier;
        $properties['combatModifier'] = $this->CombatModifier;
        $properties['finesseModifier'] = $this->FinesseModifier;
        $properties['influenceModifier'] = $this->InfluenceModifier;

        $properties['type'] = 'Attachment';

        return $properties;
    }

}