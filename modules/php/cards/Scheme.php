<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class Scheme extends Card
{
    public int $InitiativeModifier;
    public int $PanacheModifier;

    public function __construct()
    {
        parent::__construct();

        $this->InitiativeModifier = 0;
        $this->PanacheModifier = 0;
    }
}