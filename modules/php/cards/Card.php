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
    public $Traits = [];
    public array $conditions = [];

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

    public function handleEvent($event)
    {
        // Do nothing by default
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
        $properties['conditions'] = $this->conditions;

        return $properties;
    }
}