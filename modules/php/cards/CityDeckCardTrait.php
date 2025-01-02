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
}