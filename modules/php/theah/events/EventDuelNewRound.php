<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventDuelNewRound extends Event
{
    public int $duelId;
    public int $round;
    public int $playerId;
    public int $actorId;
    public int $challengerThreat;
    public int $defenderThreat;

    public function __construct()
    {
        parent::__construct();

        $this->duelId = 0;
        $this->round = 0;
        $this->playerId = 0;
        $this->actorId = 0;
        $this->challengerThreat = 0;
        $this->defenderThreat = 0;
    }
}