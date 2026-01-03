<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCharacterCombatModified extends Event
{
    public int $PlayerId;
    public int $CharacterId;
    public int $OldCombat;
    public int $NewCombat;
    public string $Reason;

    public function __construct()
    {
        parent::__construct();

        $this->PlayerId = 0;
        $this->CharacterId = 0;
        $this->OldCombat = 0;
        $this->NewCombat = 0;
        $this->Reason = '';
    }
}