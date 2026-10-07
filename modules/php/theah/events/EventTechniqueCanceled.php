<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventTechniqueCanceled extends Event
{
    public int $playerId;
    public string $techniqueId;
    // WHY: Opponent cancel of effects still counts as performing the Technique
    // (Eddie / Valeri + LTSSD). Player abort (Bastien Back) leaves this false so
    // the main Technique slot stays open. See recordCanceledAbilityInDuelTable.
    public bool $countsAsMainTechnique;

    public function __construct()
    {
        parent::__construct();
        $this->playerId = 0;
        $this->techniqueId = "";
        $this->countsAsMainTechnique = false;
    }
}