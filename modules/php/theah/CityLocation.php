<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

class CityLocation
{
    public string $Name;
    public int $Reknown;
 
    public function __construct($name)
    {
        $this->Name = $name;
        $this->Reknown = 0;
    }
}