<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class FactionAttachment extends Attachment implements IFactionCard, IWealthCost
{
    use FactionCardTrait;
    use WealthCostTrait;
}