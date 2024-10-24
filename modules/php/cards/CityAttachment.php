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

}