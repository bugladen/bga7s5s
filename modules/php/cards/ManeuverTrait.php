<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;

trait ManeuverTrait
{
    protected Array $Maneuvers = [];

    public function addManeuverProperties(&$properties)
    {
        //Add maneuver specific properties
        $properties['numberofManeuvers'] = count($this->Maneuvers);
    }
}