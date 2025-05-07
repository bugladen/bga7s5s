<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;

trait ManeuverTrait
{
    protected Array $Maneuvers = [];

    public function getManeuvers(): Array
    {
        return $this->Maneuvers;
    }

    public function addManeuverProperties(Game $game, &$properties)
    {
        //Add maneuver specific properties
        $properties['numberofManeuvers'] = count($this->Maneuvers);
        $properties['maneuvers'] = $this->getManeuversArray($game, $mustBeAvailable = false);
    }

    public function getManeuverById($id): ?Maneuver
    {
        foreach ($this->Maneuvers as $maneuver) {
            if ($maneuver->Id == $id)
                return $maneuver;
        }
        return null;
    }

    public function getManeuversArray(Game $game, bool $mustBeAvailable = false): Array
    {
        $array = [];
        foreach ($this->Maneuvers as $maneuver) {
            if ($mustBeAvailable && !$maneuver->isAvailable())
                continue;
            $array[] = [
                "id" => $maneuver->Id, 
                "name" => $game->translate($maneuver->Name),
                "shortName" => $game->translate($maneuver->ShortName),
                "available" => $maneuver->isAvailable()
            ];
        }

        return $array;
    }

    public function updateManeuverOwnerIds($id)
    {
        foreach ($this->Maneuvers as $maneuver) {
            $maneuver->setOwnerId($id);
        }
    }
}