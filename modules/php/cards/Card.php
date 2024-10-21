<?php

abstract class Card
{
    public string $Name;
    public string $Image;
    public string $Expansion;
    public int $CardNumber;
    public string $Faction;
    public $Traits = [];

    public function __construct()
    {
        $this->Name = "";
        $this->Image = "";
        $this->Expansion = "";
        $this->CardNumber = 0;
        $this->Faction = "";
    }
}