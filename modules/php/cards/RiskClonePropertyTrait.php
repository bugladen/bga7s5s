<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;

/**
 * Shared by temporary Risk shells (01106 / 01124 / 01154_RiskClone).
 * Requires the using class to expose public int $ClonedCardId.
 */
trait RiskClonePropertyTrait
{
    public function getPropertyArray(Game $game): array
    {
        $properties = parent::getPropertyArray($game);

        if ($this->ClonedCardId <= 0) {
            return $properties;
        }

        $cloned = $game->getCardObjectFromDb($this->ClonedCardId);
        if ($cloned === null) {
            return $properties;
        }

        $source = $cloned->getPropertyArray($game);

        // WHY: Clone shells only copy Name/Image/cost/action at creation. Text
        // tooltips (and any client UI reading printed card details) need the
        // original Risk's Set, Card #, R/P/T, Text, traits, etc. Overlay those
        // without replacing the clone's identity (id, location, actions).
        foreach ([
            'name',
            'image',
            'text',
            'expansionName',
            'cardNumber',
            'faction',
            'riposte',
            'dashedRiposte',
            'parry',
            'dashedParry',
            'thrust',
            'dashedThrust',
            'wealthCost',
            'traits',
        ] as $key) {
            if (array_key_exists($key, $source)) {
                $properties[$key] = $source[$key];
            }
        }

        return $properties;
    }
}
