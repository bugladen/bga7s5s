# Deck Assignment - Random Set Filter

## What

`State_DeckAssignment::onEnteringState` previously had only two branches:
- MANUAL → `pickDecks`
- anything else → random from the entire `StarterDecks::$decksJson` pool

Two random options exist that were not being honored:
- `OPTIONS_PLAYER_DECKS_RANDOM_CORE` (1)
- `OPTIONS_PLAYER_DECKS_RANDOM_TOOTH_AND_CLAW` (2)

Each starter deck has a `set` field — either `"Core"` or `"TaC"`. Added a filter step before the per-player random loop so the pool is narrowed to the requested set.

## Why

The option exists in `Game.php` but the state didn't differentiate, so picking "Random Core" or "Random TaC" both effectively meant "Random from anything." User reported decks from the wrong set were being assigned.

## Implementation Notes

- Filter runs once before the loop, not per-player. The loop already uses `array_filter` to remove the chosen deck after each assignment, which works fine on the narrowed associative array.
- `array_rand` operates on keys; `array_filter` preserves keys, so this remains compatible.
- Used `($deck->set ?? null) === "Core"` defensively — older deck entries (if any get added without `set`) won't fatally null-deref, they'll just be excluded. Reasonable since both branches require an explicit set match.
- Used `else if` (matching surrounding style) and the same `===` comparison the MANUAL branch uses.

## Considered Alternatives

- Filter inside the loop body — wasteful, runs N times for the same predicate.
- Move filtering into `StarterDecks` as a helper — overkill for one call site. Keep it inline until a second consumer appears.
- Default branch (unrecognized option) — left as "all decks" (status quo). Not relevant unless a new option value is added without code changes.
