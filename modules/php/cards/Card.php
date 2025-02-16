<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class Card
{
    public int $Id; 
    public int $OwnerId;
    public int $ControllerId;
    public string $Name;
    public string $Image;
    public string $ExpansionName;
    public int $ExpansionNumber;
    public int $CardNumber;
    public string $Faction;
    public bool $Engaged;
    public Array $Traits = [];
    public Array $Conditions = [];

    public string $Location;
    public bool $IsUpdated;
    public int $Reknown;

    public function __construct()
    {
        $this->Id = 0;
        $this->OwnerId = 0;
        $this->ControllerId = 0;
        $this->Name = "";
        $this->Image = "";
        $this->ExpansionName = "";
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->Faction = "";
        $this->Engaged = false;

        $this->Location = "";
        $this->IsUpdated = false;
        $this->Reknown = 0;
    }

    public function setId($id)
    {
        $this->Id = $id;
        if ($this instanceof IHasTechniques) {
            $this->updateTechniqueOwnerIds($id);
        }
        if ($this instanceof IHasManeuvers) {
            $this->updateManeuverOwnerIds($id);
        }
    }

    public function eventCheck($event)
    {
        if ($this instanceof IHasTechniques) {
            foreach ($this->getTechniques() as $technique) {
                $technique->eventCheck($event);
            }
        }
        if ($this instanceof IHasManeuvers) {
            foreach ($this->getManeuvers() as $maneuver) {
                $maneuver->eventCheck($event);
            }
        }
    }
    
    public function handleEvent($event)
    {
        if ($this instanceof IHasTechniques) {
            foreach ($this->getTechniques() as $technique) {
                $technique->handleEvent($event);
            }
        }
        if ($this instanceof IHasManeuvers) {
            foreach ($this->getManeuvers() as $maneuver) {
                $maneuver->handleEvent($event);
            }
        }
    }

    public function addCondition($condition)
    {
        $this->Conditions[] = $condition;
    }

    public function removeCondition($condition)
    {
        $index = array_search($condition, $this->Conditions);
        if ($index !== false) {
            unset($this->conditions[$index]);
        }
    }
    
    public function getPropertyArray()
    {
        $properties = [
            'id' => $this->Id,
            'ownerId' => $this->OwnerId,
            'controllerId' => $this->ControllerId,
            'name' => $this->Name,
            'image' => $this->Image,
            'faction' => $this->Faction,
            'location' => $this->Location,
            'engaged' => $this->Engaged,
            'reknown' => $this->Reknown,
        ];

        $properties['type'] = 'Card';
        $properties['traits'] = $this->Traits;
        $properties['conditions'] = $this->Conditions;

        if ($this instanceof IWealthCost) $this->addWealthCostProperties($properties);
        if ($this instanceof ICityDeckCard) $this->addCityProperties($properties);
        if ($this instanceof IFactionCard) $this->addFactionProperties($properties);
        if ($this instanceof IHasTechniques) $this->addTechniqueProperties($properties);
        if ($this instanceof IHasManeuvers) $this->addManeuverProperties($properties);

        return $properties;
    }
}