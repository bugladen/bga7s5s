<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class FactionCharacter extends Character implements IFactionCard, IWealthCost
{
    use FactionCardTrait;
    use WealthCostTrait;
}