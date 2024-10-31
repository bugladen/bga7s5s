<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class CityCharacter extends CityDeckCard
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

    public int $WealthCost;
    public bool $Negotiable;

    public function __construct(){
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

        $this->WealthCost = 0;
        $this->Negotiable = false;
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

        $properties['wealthCost'] = $this->WealthCost;
        $properties['negotiable'] = $this->Negotiable;

        $properties['type'] = 'Character';


        return $properties;
    }
}
