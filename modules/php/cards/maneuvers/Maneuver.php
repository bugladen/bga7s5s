<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CardAbility;

abstract class Maneuver extends CardAbility
{
    public bool $ResetOnDuelEnd;
    public bool $ResetOnDayEnd;

    public function __construct()
    {
        parent::__construct();
        $this->ResetOnDuelEnd = true;
        $this->ResetOnDayEnd = false;
    }
}