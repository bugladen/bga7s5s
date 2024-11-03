<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class Card
{
    public int $Id; 
    public int $OwnerId;
    public string $Name;
    public string $Image;
    public string $ExpansionName;
    public int $ExpansionNumber;
    public int $CardNumber;
    public string $Faction;
    public $Traits = [];

    public string $Location;

    public function __construct()
    {
        $this->Id = 0;
        $this->OwnerId = 0;
        $this->Name = "";
        $this->Image = "";
        $this->ExpansionName = "";
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->Faction = "";

        $this->Location = "";
    }

    public function getPropertyArray()
    {
        $properties = [
            'id' => $this->Id,
            'ownerId' => $this->OwnerId,
            'name' => $this->Name,
            'image' => $this->Image,
            'faction' => $this->Faction,
            'location' => $this->Location,
        ];

        $properties['type'] = 'Card';

        return $properties;
    }
}