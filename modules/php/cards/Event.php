<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class Event extends CityDeckCard
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getPropertyArray()
    {
        $properties = parent::getPropertyArray();

        return $properties;
    }
}