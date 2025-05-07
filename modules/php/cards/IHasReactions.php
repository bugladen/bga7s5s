<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;

interface IHasReactions
{
    public function getReactions(): Array;

    public function addReactionProperties(Game $game, &$properties);

    public function anyReactionsAvailable(): bool;

    public function getReactionById($id): ?CardReaction;

    public function getReactionNames(Game $game, $includeAvailable = false): Array;

    public function getReactionsArray(Game $game, bool $mustBeAvailable = false): Array;

    public function reactionFromCard(Game $game, int $state, string $internalId, string $reactionId): void;

    public function updateArgsFromReaction(Game $game, Array &$args, int $state, string $stateName, string $internalId): void;
    
    public function updatePayForArgsFromReaction(Game $game, Array &$args, int $state, string $stateName, string $internalId): void;

    public function updateReactionOwnerIds($id);
}