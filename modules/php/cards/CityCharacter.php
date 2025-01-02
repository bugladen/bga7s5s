<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class CityCharacter extends Character implements ICityDeckCard, IWealthCost
{
    use CityDeckCardTrait;
    use WealthCostTrait;

    public bool $Negotiable;

    public function __construct()
    {
        parent::__construct();

        $this->Negotiable = false;
    }

    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();

        $this->addCityProperties($properties);
        $this->addWealthCostProperties($properties);

        $properties['negotiable'] = $this->Negotiable;

        return $properties;
    }
}
