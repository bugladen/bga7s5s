<?php

abstract class Faction extends Card
{
    public int $WealthCost;
    public int $Riposte;
    public int $Parry;
    public int $Thrust;

    public function __construct($type, $value)
    {
        parent::__construct($type, $value);

        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->Parry = 0;
        $this->Thrust = 0;
    }

}