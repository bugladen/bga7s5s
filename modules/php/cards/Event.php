<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class Event extends Card
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getPropertyArray()
    {
        $properties = parent::getPropertyArray();

        $properties['type'] = 'Event';

        return $properties;
    }


}