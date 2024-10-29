<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class Card
{
    public int $Id; 
    public string $Name;
    public string $Image;
    public string $ExpansionName;
    public int $ExpansionNumber;
    public int $CardNumber;
    public string $Faction;
    public $Traits = [];

    public function __construct()
    {
        $this->Id = 0;
        $this->Name = "";
        $this->Image = "";
        $this->ExpansionName = "";
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->Faction = "";
    }

    public function getPropertyArray()
    {
        $properties = [
            'name' => $this->Name,
            'image' => $this->Image,
            'faction' => $this->Faction,
        ];

        return $properties;
    }
}