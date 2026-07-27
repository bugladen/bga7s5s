<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

trait CityDeckCardTrait
{
    public int $CityCardNumber = 0;

    function addCityProperties(&$properties)
    {
        //Add city deck card specific properties
        $properties['cityCardNumber'] = $this->CityCardNumber;
        $properties['deckOrigin'] = 'City';
    }

    // WHY: Default true for city deck cards. Siren's Scream (_01179) and similar
    // override when discard is blocked (e.g. Renown still on the card). Discard
    // choosers filter with this so a lone undiscardable card does not stick the UI.
    public function canBeDiscardedFromCity(): bool
    {
        return true;
    }
}