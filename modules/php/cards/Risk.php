<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

class Risk extends Card implements IFactionCard, IWealthCost
{
    use FactionCardTrait;
    use WealthCostTrait;

    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();

        $properties['type'] = 'Risk';

        return $properties;
    }
}