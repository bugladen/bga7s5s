<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;

trait TechniqueTrait
{
    protected Array $Techniques = [];

    public function getTechniques(): Array
    {
        return $this->Techniques;
    }

    public function addTechniqueProperties(Game $game, &$properties)
    {
        //Add technique specific properties
        $properties['numberofTechniques'] = count($this->Techniques);
        $properties['techniques'] = $this->getTechniquesArray($game, $mustBeAvailable = false);
    }

    public function anyTechniquesAvailable(): bool
    {
        foreach ($this->Techniques as $technique) {
            if ($technique->isAvailable()) {
                return true;
            }
        }
        return false;
    }

    public function getTechniquesArray(Game $game, bool $mustBeAvailable = false): Array
    {
        $array = [];
        foreach ($this->Techniques as $technique) {
            if ($mustBeAvailable && !$technique->isAvailable()) {
                continue;
            }
            $array[] = [
                "id" => $technique->Id, 
                "name" => $game->translate($technique->Name),
                "shortName" => $game->translate($technique->ShortName),
                "available" => $technique->isAvailable()
            ];
        }

        return $array;
    }

    public function getTechniqueById($id): ?Technique
    {
        foreach ($this->Techniques as $technique) {
            if ($technique->Id == $id)
                return $technique;
        }
        return null;
    }

    public function updateTechniqueOwnerIds($id)
    {
        foreach ($this->Techniques as $technique) {
            $technique->setOwnerId($id);
        }
    }
}