<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Reaction;

/** Game-framework (Theah-owned, not card-owned) Reaction fixture that records performReaction. */
class GenericTheahReaction extends Reaction
{
    public string $Id = 'GenericTheahReaction';

    /** @var list<array{state:int,internalId:string,reactionId:string}> */
    public array $performed = [];

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        $this->performed[] = ['state' => $state, 'internalId' => $internalId, 'reactionId' => $reactionId];
    }
}
