<?php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

interface IWealthCost
{
    public function setWealthCost(int $wealthCost);

    public function getWealthCost(): int;

    public function addWealthCostProperties(&$properties);
}