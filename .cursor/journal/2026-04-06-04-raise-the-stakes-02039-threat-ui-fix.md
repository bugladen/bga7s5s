# Raise the Stakes (_02039) — End-of-Round Threat Timing Fix

## The Bug

Eddie reported that the maneuver for `_02039` ("Add a threat to both participants" at end of round) was:
1. Updating the CURRENT round's ending threat display (wrong — threat resolution is already complete)
2. Not correctly showing the added threat as starting threat in the NEXT round

## Why the Original Approach Was Wrong

The original implementation used `EventThreatModified(1, 1)` which:
- Modified the current round's `ending_challenger_threat` and `ending_defender_threat` in the DB
- Sent `updateRoundThreats` notification, visually changing the current round's ending threat chips
- Also modified `wounds_taken` for the current round (wrong — wounds are already resolved)

Per the rules, "at the end of the round" is AFTER the Resolve Threat step. Any remaining threat has already been applied as wounds. Threat added at end-of-round applies to the NEXT round, not the current one.

## The Fix — Pending Threat Globals

Instead of `EventThreatModified`, the maneuver now stores pending threat in globals:
- `Game::PENDING_CHALLENGER_THREAT` — accumulated pending threat for challenger
- `Game::PENDING_DEFENDER_THREAT` — accumulated pending threat for defender

These globals use accumulation (`get` + `set`) so multiple effects could stack.

### Flow

1. `Maneuver_02039` handles `EventDuelEndOfRound` → sets pending threat globals (+1 each)
2. Current round's DB row is NOT modified — ending threats and wounds stay accurate
3. `stDuelNextPlayer` checks for pending threat before ending the duel (prevents premature duel end when both ending threats are 0 but pending threat exists)
4. `stDuelNewRound` reads and applies pending threat globals → adds them to `$challengerThreat`, `$defenderThreat`, and actor's `$wounds` before inserting the new round row
5. `stDuelEnd` cleans up pending threat globals

### `stDuelNewRound` Change — Reading Both Ending Threats from DB

Also changed `stDuelNewRound` to read BOTH `ending_challenger_threat` and `ending_defender_threat` from the previous round's DB row, instead of hard-coding the old actor's side to 0. In normal flow these are 0 anyway (consumed in `stDuelEndOfRound`), but this is more robust for any future effects that might modify ending threats.

### Wounds Calculation

When applying pending threat to the new round, `$wounds` only gets the ACTOR's pending threat added (not both sides), because `wounds_taken` tracks the actor's outgoing threat potential.

## Files Changed
- `Game.php` — Added `PENDING_CHALLENGER_THREAT` and `PENDING_DEFENDER_THREAT` constants
- `Maneuver_02039.php` — Replaced `EventThreatModified` with pending threat globals; removed `EventFactory` import, added `Game` import
- `StatesTrait.php`:
  - `stDuelNewRound()` — Reads both ending threats from DB; applies pending threat globals before row insert
  - `stDuelNextPlayer()` — Checks pending threat before ending duel on zero threats
  - `stDuelEnd()` — Cleans up pending threat globals
