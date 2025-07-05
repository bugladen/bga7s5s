<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers;

class Maneuver_01108 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Draw a Card");
    }
}