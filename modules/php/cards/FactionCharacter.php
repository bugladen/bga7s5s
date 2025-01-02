<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class FactionCharacter extends Character implements IFactionCard, IWealthCost
{
    use FactionCardTrait;
    use WealthCostTrait;

    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();
        $this->addFactionProperties($properties);
        $this->addWealthCostProperties($properties);

        return $properties;
    }
}