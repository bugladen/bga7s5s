# Tournament deck lock feature flag

## Request
User wants a global in Game.php to turn tournament deck locking on/off.

## Approach
Added `Game::TOURNAMENT_LOCK_DECKS = true` next to the game options constants (not a BGA `$globals` value). WHY: this is a deploy-time switch for whether the Tournament API store/restore path runs at all — not per-table runtime state. Flipping a const and re-uploading is the intended control; a game-state global would need init + UI for no benefit.

Gated every store/restore site with `Game::TOURNAMENT_LOCK_DECKS && isTournament()`:
- `FrameworkActionsTrait::actPickDeck` — store after pick
- `State_DeckAssignment` — restore + lock random assignment (`$lockTournamentDecks` local)
- `StatesTrait::stPickDecksInit` — skip players who already have a locked deck

Default stays `true` so current tournament behavior is unchanged. Set to `false` to let players re-pick each tournament game.

## Harness
Tests load `FakeGame`, not real `Game.php`. Mirrored `TOURNAMENT_LOCK_DECKS = true` there or FrameworkActions / StatesTrait_Init blow up with undefined constant.

## Not done
No harness override for the const (tests keep assuming lock-on). If we need an off-path regression later, make it a public static bool or add a Game method the harness can stub.
