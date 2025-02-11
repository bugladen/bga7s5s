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

    public function getManeuversArray(bool $mustBeActive = false): Array
    {
        $array = [];
        foreach ($this->Maneuvers as $maneuver) {
            if ($mustBeActive && !$maneuver->IsActive) {
                continue;
            }
            $array[] = ["id" => $maneuver->Id, "name" => $maneuver->Name];
        }

        return $array;
    }

}