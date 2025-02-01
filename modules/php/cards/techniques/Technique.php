<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CardAbility;

abstract class Technique extends CardAbility
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