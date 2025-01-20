<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

trait TechniqueTrait
{
    private Array $techniques = [];

    function addTechniqueProperties(&$properties)
    {
        //Add technique specific properties
        $properties['numberofTechniques'] = count($this->techniques);
    }
}