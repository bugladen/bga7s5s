<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;

interface IHasManeuvers
{
    public function getManeuvers(): Array;

    public function addManeuverProperties(&$properties);

    public function getManeuverById($id): ?Maneuver;

    public function getManeuversArray(): Array;

    public function updateManeuverOwnerIds($id);
}