# Mireli's Revision (01135) — forced gamble was consuming Finesse

## Bug

Card text: "They gamble and play one for free instead. (It does not count against their total played gambles.)"

`Reaction_01135` transitioned into the normal gamble flow without setting a free `GAMBLE_TYPE`. `actGambleCardChosen` only skips writing `duel_round.gambled = 1` for non-`NORMAL` types, so the forced replacement was counted like a voluntary gamble. If the cancelled card had itself been gambled, that flag was already set and stayed set — also wrong under the "instead" reading (replacement should not leave a spent gamble on the cancelled play).

## Fix

1. Added `Game::GAMBLE_TYPE_FREE = 2`. Same play-as-combat-card path as `NORMAL`, but does not update `duel_round.gambled` (mirrors the counting half of `ROLL_THE_DICE` without the sink/don't-play semantics).

2. On `EventRiskReactionTriggered` in `Reaction_01135`:
   - Clear `duel_round.gambled` for the current round (refunds a voluntary gamble that got cancelled).
   - Set `GAMBLE_TYPE_FREE` + reveal count/explanations (so both `DUEL_GAMBLE_SETUP` and `DUEL_GAMBLE_REVEALED` entry targets work).

3. `stApplyCombatCardStats`: `event->gambled` is true if DB flag is set OR (`DUEL_GAMBLED` and not Roll-the-Bones additive stats). WHY: FREE leaves DB flag NULL for Finesse accounting, but Sanjay / "gambled combat card" effects still need to see a gambled play. RtB excluded because values attach to non-gambled 01114.

## Why not reuse ROLL_THE_DICE

That type also skips playing the chosen card as a combat card and sinks it into 01114. Mireli still plays the replacement as a normal combat card.

## Unfinished / watch

Cost-events path still transitions `01135` → `DUEL_GAMBLE_REVEALED` (skips SETUP / `EventGambleSetup`). Reveal count is now set in the reaction so that path is less wrong, but Devil Jonah's Bones-style setup listeners still won't run on that entry. Separate from this bug.
