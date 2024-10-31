<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class CityDeckCard extends Card
{
    public int $CityCardNumber;

    public function __construct()
    {
        parent::__construct();

        $this->CityCardNumber = 0;
    }

    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();

        //Add city deck card specific properties
        $properties['cityCardNumber'] = $this->CityCardNumber;

        $properties['deckOrigin'] = 'City';

        return $properties;
    }
}