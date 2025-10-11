<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
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

    public function createButtonProperty(Game $game, string $text, string $reactionId): array
    {
        return [
            'text' => $game->translate($text),
            'reaction' => $reactionId,
        ];
    }

    public function getReactionAnnouncement(Game $game, int $state, string $internalId, string $reactionId): string 
    {
        return '';
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void {}

    public function actFromReactionPass(Game $game, int $state): void { }

    public function actFromReactionWithId(Game $game, int $state, string $stateName, int $id): void  { }
    
    public function actFromReactionWithIds(Game $game, int $state, string $stateName, array $ids): void  { }

    public function stateFromReaction(Game $game, int $state, string $stateName): void { }

    public function getArgsFromReaction(Game $game, int $state, string $stateName): array   { return []; }

    public function getNumberOfGambleCardsToReveal(Theah $theah, Character $actor, Array &$explanations): int { return 0; }
}