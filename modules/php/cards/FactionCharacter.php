<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class FactionCharacter extends Character implements IFactionCard, IWealthCost
{
    use FactionCardTrait;
    use WealthCostTrait;

    public function __construct()
    {
        parent::__construct();

        $this->CardBackImage = "img/cards/backs/faction.jpg";
    }
}