<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

/**
 * Shared behaviour of the Generic*Action fixtures: availability is a test-controlled flag and
 * every act-from-action dispatch is recorded in $calls as [hook, args].
 *
 * WHY a trait: the fixtures extend three different Action bases (CharacterAction, RiskAction,
 * LocationAction) and must behave identically; FrameworkActionsTrait::actFromCard* tests assert
 * on exactly what Card/Action dispatch handed down.
 */
trait RecordingActionTrait
{
    /** Test switch: false makes the action unavailable regardless of base-class rules. */
    public bool $available = true;

    /** @var list<array{0:string,1:array}> */
    public array $calls = [];

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        return $this->available && parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck);
    }

    public function actFromActionPass(Game $game, int $state): void
    {
        $this->calls[] = ['pass', [$state]];
    }

    public function actFromActionRevealHand(Game $game, int $state, string $stateName = ''): void
    {
        $this->calls[] = ['revealHand', [$state, $stateName]];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        $this->calls[] = ['withId', [$state, $stateName, $id]];
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        $this->calls[] = ['withIds', [$state, $stateName, $ids]];
    }

    public function actFromActionWithActionId(Game $game, int $state, string $stateName, int $actionSourceId, string $actionId): void
    {
        $this->calls[] = ['withActionId', [$state, $stateName, $actionSourceId, $actionId]];
    }
}
