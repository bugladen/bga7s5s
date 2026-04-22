<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;

interface IHasManeuvers
{
    public function getManeuvers(): Array;

    public function addManeuverProperties(Game $game, &$properties);

    public function getManeuverById($id): ?Maneuver;

    public function getManeuversAvailableToPlayer(Game $game, int $playerId): Array;

    public function getManeuversArray(Game $game, bool $mustBeAvailable = false): Array;

    public function updateManeuverOwnerIds($id);

    public function addManeuver(Maneuver $maneuver, Game $game);

    public function removeManeuver(Maneuver $maneuver, Game $game);
}