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

    public function getPropertyArray()
    {
        $properties = parent::getPropertyArray();

        //Add scheme specific properties
        $properties['initiative'] = $this->Initiative;
        $properties['panacheModifier'] = $this->PanacheModifier;

        $properties['type'] = 'Scheme';

        return $properties;
    }
}