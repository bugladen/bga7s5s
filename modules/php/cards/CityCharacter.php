<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;

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

    public function getPropertyArray(Game $game): array
    {
        $properties = parent::getPropertyArray($game);

        $properties['negotiable'] = $this->Negotiable;

        return $properties;
    }
}
