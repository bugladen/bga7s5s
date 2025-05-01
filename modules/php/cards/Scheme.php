<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;

abstract class Scheme extends Card
{
    public int $Initiative;
    public int $PanacheModifier;

    public function __construct()
    {
        parent::__construct();

        $this->Initiative = 0;
        $this->PanacheModifier = 0;
    }

    public function hasWhenRevealedEffect() : bool
    {
        return false;
    }

    public function getPropertyArray(Game $game)
    {
        $properties = parent::getPropertyArray($game);

        //Add scheme specific properties
        $properties['initiative'] = $this->Initiative;
        $properties['panacheModifier'] = $this->PanacheModifier;

        $properties['type'] = 'Scheme';

        return $properties;
    }
}