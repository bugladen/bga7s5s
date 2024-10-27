<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class Scheme extends Card
{
    public int $Initiative;
    public int $PanacheModifier;
    public $Traits = [];

    public function __construct()
    {
        parent::__construct();

        $this->Initiative = 0;
        $this->PanacheModifier = 0;
    }
}