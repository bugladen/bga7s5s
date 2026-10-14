# Mireli's Revision (01135) — replacement must inherit spent gamble

## Bug

When Mireli cancelled a *gambled* combat card, the forced FREE replacement always showed as not gambled on the dueling line and did not count toward `count(gambled)` Finesse. Opponent got an extra gamble later.

## Root cause (over-correction from 2026-09-12)

`Reaction_01135` on trigger did:

```sql
UPDATE duel_round SET gambled = NULL WHERE ...
```

plus `GAMBLE_TYPE_FREE` (which correctly does not *write* `gambled=1`). Clearing wiped a voluntary gamble that had already been spent when the cancelled card was chosen. Card text "does not count against their total played gambles" was read as "always clear," but the intended reading is:

- hand play → FREE → leave `gambled` NULL (replacement is free; no Finesse spend)
- voluntary gamble → FREE → leave existing `gambled=1` (replacement inherits the spent slot; do not refund)

## Fix

Removed the clear. Kept `GAMBLE_TYPE_FREE`. Updated WHY comments on `Reaction_01135`, `Game::GAMBLE_TYPE_FREE`, `actGambleCardChosen`, `stApplyCombatCardStats`.

Regression: assert no `duel_round`/`gambled`/`NULL` DbQuery on trigger. FakeGame now appends to `$dbQueries` for that assertion.

## Unfinished

None for this bug. Live table still needs a Mireli-on-gambled-card smoke if you want UI class `_7sfs-duel-row-combat-card-gambled` confirmed on the replacement row.
