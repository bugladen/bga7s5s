<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class FactionAttachment extends Attachment implements IFactionCard, IWealthCost
{
    use FactionCardTrait;
    use WealthCostTrait;

    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();
        $this->addFactionProperties($properties);
        $this->addWealthCostProperties($properties);

        $properties['type'] = 'Attachment';

        return $properties;
    }
}