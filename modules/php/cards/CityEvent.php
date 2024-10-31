<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class CityEvent extends CityDeckCard
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();

        $properties['type'] = 'Event';

        return $properties;
    }
}