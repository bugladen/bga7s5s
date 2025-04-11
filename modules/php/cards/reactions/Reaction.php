<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CardAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class Reaction extends CardAbility
{
    public function __construct()
    {
        parent::__construct();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuskEndOfDay)
        {
            $this->Used = false;
            $card = $this->getOwningCard($event->theah);
            $card->IsUpdated = true;
        }
    }

    public function isAvailable(): bool
    {
        return ! $this->Used;
    }

    public function getReactionDescription(Theah $theah): string
    {
        return '';
    }

    public function getReactionPayForDescription(Theah $theah): string
    {
        return '${you} must now select cards to pay for ' . $this->Name . ': ';
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        return [];
    }

    public function createButtonProperty(string $text, string $reaction): array
    {
        return [
            'text' => $text,
            'reaction' => $reaction,
        ];
    }

    public function getReactionAnnouncement(Game $game, int $state, string $internalId, string $reactionId): string 
    {
        return '';
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void {}
    
    public function reactionPaidFor(Game $game, int $state, string $internalId, string $reactionId): void {}
}