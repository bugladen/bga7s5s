<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

class CityLocation
{
    public string $Name;
    public int $Renown;
    public int $Controller;
    public bool $CanBeClaimed = true;

    public function __construct(string $name)
    {
        $this->Name = $name;
        $this->Renown = 0;
        $this->Controller = 0;
        $this->CanBeClaimed = true;
    }

    public function isControlled(): bool
    {
        return $this->Controller > 0;
    }
}