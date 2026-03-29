<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

class CityLocation
{
    public string $Name;
    public int $Renown;
    public int $Controller;
 
    public function __construct($name)
    {
        $this->Name = $name;
        $this->Renown = 0;
        $this->Controller = 0;
    }

    public function isControlled(): bool
    {
        return $this->Controller > 0;
    }
}