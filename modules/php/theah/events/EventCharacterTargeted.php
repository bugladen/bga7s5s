<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

// Cancel hook when an ability targets a card. targetId is usually a character, but
// attachment-target abilities (e.g. Shoddy Craftsmanship) put the attachment Id here.
// Unyielding Loyalty / Hexenjagd resolve the target via getCardById.
class EventCharacterTargeted extends Event
{
    public int $playerId;
    public int $targetId;
    public int $sourceId;
    public string $abilityId;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->targetId = 0;
        $this->sourceId = 0;
        $this->abilityId = '';
    }
}