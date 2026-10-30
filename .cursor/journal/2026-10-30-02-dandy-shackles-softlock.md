# Claude move Action soft-lock under Shackles

## Bug

Claude (_05Dandy) Action a: Pay 1 Wealth → choose City location. Player paid, entered `highDramaPhase05Dandya_2`, every location pick threw "Claude de la Roche is Shackled and cannot move." Soft-lock: wealth gone, state stuck.

## Why

Shackles stamps `SHACKLES_CONDITION`; `Character::eventCheckCore` vetoes non-unstoppable `EventCardMoving`. Action_05Dandya paid in step 1, then `eventCheck` on the move in step 2. Same soft-fail class noted in Shackles journal (HD movers that only fail at move time). Rules: "Equipped character cannot move" is absolute — Claude's own Action does not bypass Shackles. Do not mark the move `unstoppable`.

## Fix

`Action_05Dandya`:
1. `isAvailableToPlayer` false when Shackled/Harpooned (Technique_01036 / Maneuver_01059 shape).
2. Pay step throws before discard if still blocked (stale client).
3. Location step: if already paid and still blocked, notify + `ActionResolved` with no move — unsticks live tables after deploy without refunding wealth.

Zombie on `_2` routes through that branch when conditioned.

## Tests

Card_05Dandy: unavailable while Shackled; pay refuses; location choice resolves without move.

## Do not "fix"

Do not make Claude's move unstoppable to "beat" Shackles — that would break printed Shackles. Do not refund on the stuck-path resolve — payment already happened as game rules; recovery is exit the state.
