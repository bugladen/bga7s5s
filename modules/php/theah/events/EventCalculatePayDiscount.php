<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCalculatePayDiscount extends Event
{
    public int $playerId;
    public int $cardId;
    public int $payStateType;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->cardId = 0;
        $this->payStateType = 0;

        $this->runEventHubAfterCards = true;
    }
}