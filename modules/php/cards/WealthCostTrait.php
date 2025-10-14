<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

trait WealthCostTrait
{
    public int $WealthCost = 0;

    function setWealthCost(int $wealthCost)
    {
        $this->WealthCost = $wealthCost;
    }

    function getWealthCost(): int
    {
        return $this->WealthCost;
    }

    function addWealthCostProperties(&$properties)
    {
        $properties['wealthCost'] = $this->WealthCost;
    }
}