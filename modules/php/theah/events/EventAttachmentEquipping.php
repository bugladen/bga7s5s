<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventAttachmentEquipping extends Event
{
    public int $playerId;
    public int $characterId;
    public int $attachmentId;
    public int $discount;
    public int $cost;
    public bool $asAction;
    public string $explanations;
    public bool $messageHidden;
    public ?int $sourceId;
    public ?string $abilityId;

    public function __construct()
    {
        parent::__construct();

        $this->characterId = 0;
        $this->attachmentId = 0;
        $this->discount = 0;
        $this->cost = 0;
        $this->asAction = true;
        $this->explanations = '';
        $this->messageHidden = false;
        $this->runEventHubAfterCards = true;
    }
}
