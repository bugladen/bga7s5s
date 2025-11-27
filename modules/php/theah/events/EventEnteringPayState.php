<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventEnteringPayState extends Event
{
    public int $playerId;
    public int $cardId;
    public string $internalId;
    public int $payStateType;


    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->cardId = 0;
        $this->internalId = '';
        $this->payStateType = 0;

        $this->runEventHubAfterCards = true;
    }
}