<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

class Risk extends FactionDeckCard
{
    public function __construct()
    {
        parent::__construct();

    }

    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();

        $properties['type'] = 'Risk';

        return $properties;
    }
}