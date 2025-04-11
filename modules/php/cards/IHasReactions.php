<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\Reaction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;

interface IHasReactions
{
    public function getReactions(): Array;

    public function addReactionProperties(&$properties);

    public function anyReactionsAvailable(): bool;

    public function getReactionById($id): ?Reaction;

    public function getReactionNames($includeAvailable = false): Array;

    public function getReactionsArray(): Array;

    public function reactionFromCard(Game $game, int $state, string $internalId, string $reactionId): void;

    public function updateArgsFromReaction(Game $game, Array &$args, int $state, string $stateName, string $internalId): void;
    
    public function updatePayForArgsFromReaction(Game $game, Array &$args, int $state, string $stateName, string $internalId): void;

    public function updateReactionOwnerIds($id);
}