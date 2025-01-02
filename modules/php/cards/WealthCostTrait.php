<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

trait WealthCostTrait
{
    public int $WealthCost = 0;

    function addWealthCostProperties(&$properties)
    {
        $properties['wealthCost'] = $this->WealthCost;
    }
}