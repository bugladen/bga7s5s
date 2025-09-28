<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCharacterFinesseModifed extends Event
{
    public int $PlayerId;
    public int $CharacterId;
    public int $OldFinesse;
    public int $NewFinesse;
    public string $Reason;

    public function __construct()
    {
        parent::__construct();

        $this->PlayerId = 0;
        $this->CharacterId = 0;
        $this->OldFinesse = 0;
        $this->NewFinesse = 0;
        $this->Reason = '';
    }
}