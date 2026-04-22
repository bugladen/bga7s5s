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

    public function getManeuversAvailableToPlayer(Game $game, int $playerId): Array
    {
        $array = [];
        foreach ($this->Maneuvers as $maneuver) {
            if ($maneuver->isAvailableToPlayer($playerId, $game->theah))
                $array[] = $maneuver;
        }
        return $array;
    }

    public function getManeuversArray(Game $game, bool $mustBeAvailable = false): Array
    {
        $array = [];
        $playerId = $game->getActivePlayerId();
        foreach ($this->Maneuvers as $maneuver) {
            if ($mustBeAvailable && (! $maneuver->isAvailable() || ! $maneuver->isAvailableToPlayer($playerId, $game->theah)))
                continue;

            $array[] = $maneuver->getPropertyArray($game);
        }

        return $array;
    }

    public function updateManeuverOwnerIds($id)
    {
        foreach ($this->Maneuvers as $maneuver) {
            $maneuver->setOwnerId($id);
        }
    }

    public function addManeuver(Maneuver $maneuver, Game $game, bool $notify = true)
    {
        $this->Maneuvers[] = $maneuver;
        $this->IsUpdated = true;

        if ($notify)
        {
            $game->notify->all('maneuverAdded', clienttranslate('${character_inject_code} has gained Maneuver: ${maneuver_name}.'), [
                'i18n' => ['maneuver_name'],
                'character_inject_code' => $this->getInjectCode(),
                'characterId' => $this->Id,
                'maneuver' => $maneuver->getPropertyArray($game),
                'maneuver_name' => $maneuver->Name
            ]);
        }
    }

    public function removeManeuver(Maneuver $maneuver, Game $game, bool $notify = true)
    {
        $this->Maneuvers = array_values(array_filter($this->Maneuvers, fn($m) => $m->Id != $maneuver->Id));
        $this->IsUpdated = true;
        
        if ($notify)
        {
            $game->notify->all('maneuverRemoved', clienttranslate('${character_inject_code} has lost Maneuver: ${maneuver_name}.'), [
                'i18n' => ['maneuver_name'],
                'character_inject_code' => $this->getInjectCode(),
                'characterId' => $this->Id,
                'maneuverId' => $maneuver->Id,
                'maneuver_name' => $maneuver->Name
            ]);
        }
    }
}