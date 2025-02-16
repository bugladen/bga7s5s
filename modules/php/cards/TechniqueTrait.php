<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;

trait TechniqueTrait
{
    protected Array $Techniques = [];

    public function getTechniques(): Array
    {
        return $this->Techniques;
    }

    public function addTechniqueProperties(&$properties)
    {
        //Add technique specific properties
        $properties['numberofTechniques'] = count($this->Techniques);
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

    public function getTechniqueNames($includeAvailable = false): Array
    {
        $names = [];
        foreach ($this->Techniques as $technique) {
            if ($technique->isAvailable())
                $names[] = $technique->name;
        }
        return $names;
    }

    public function getTechniquesArray(bool $mustBeAvailable = false): Array
    {
        $array = [];
        foreach ($this->Techniques as $technique) {
            if ($mustBeAvailable && !$technique->isAvailable()) {
                continue;
            }
            $array[] = ["id" => $technique->Id, "name" => $technique->Name];
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