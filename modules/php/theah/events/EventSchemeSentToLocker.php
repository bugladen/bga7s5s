<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventSchemeSentToLocker extends Event
{
    public int $schemeId;

    public function __construct()
    {
        parent::__construct();

        $this->schemeId = 0;
    }
}