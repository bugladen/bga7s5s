<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class FactionAttachment extends Attachment implements IFactionCard, IWealthCost
{
    use FactionCardTrait;
    use WealthCostTrait;

    public bool $CanEquipToOpponents = false;

    public function __construct()
    {
        parent::__construct();

        $this->CardBackImage = "img/cards/backs/faction.jpg";
    }

}