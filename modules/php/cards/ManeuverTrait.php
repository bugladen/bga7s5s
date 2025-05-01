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

    public function addManeuverProperties(&$properties)
    {
        //Add maneuver specific properties
        $properties['numberofManeuvers'] = count($this->Maneuvers);
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
            if ($mustBeAvailable && !$maneuver->Used)
                continue;
            $array[] = ["id" => $maneuver->Id, "name" => $game->translate($maneuver->Name)];
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