<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;

class Risk extends Card implements IFactionCard, IWealthCost
{
    use FactionCardTrait;
    use WealthCostTrait;

    public function __construct()
    {
        parent::__construct();

        $this->CardBackImage = "img/cards/backs/faction.jpg";
    }

    public function getPropertyArray(Game $game): array
    {
        $properties = parent::getPropertyArray($game);

        $properties['type'] = 'Risk';

        return $properties;
    }

    // WHY: Printed "These effects cannot be cancelled." — Night of Drinking (01109)
    // and similar Risk-cancel Reactions must not offer cancel. Override on those cards.
    public function effectsCannotBeCancelled(): bool
    {
        return false;
    }
}