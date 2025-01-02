<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class CityAttachment extends Attachment implements ICityDeckCard
{
    use CityDeckCardTrait;

    public function __construct()
    {
        parent::__construct();
    }

    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();
        
        $this->addCityProperties($properties);

        return $properties;
    }

}