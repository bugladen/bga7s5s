<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class Reaction 
{
    public function __construct()
    {
    }

    public function eventCheck(Event $event) {}

    public function handleEvent(Event $event) { }

    public function getReactionDescription(Theah $theah): string
    {
        return '';
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
}