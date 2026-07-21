# 03023 Second Wind - Threat Rollover Fix

## What was wrong in the first pass

The original Maneuver_03023 zeroed `EventCharacterBeingWounded.wounds` and assumed the threat would carry to the next round "naturally via ending_*_threat fields."

That assumption was wrong. Walked through `stDuelEndOfRound` (StatesTrait.php:1414):

1. **Line 1453** — SQL wipes `ending_<actor>_threat = 0` and `<actor>_threat_is_lethal = 0` BEFORE the wound event is queued.
2. **Line 1492-1493** — Then queues `EventCharacterBeingWounded` with `wounds = threat`.
3. `stDuelNewRound` (StatesTrait.php:1106-1112) reads `ending_<actor>_threat` from the previous round to seed the next round. Since step 1 zeroed it, the seed is 0.

So suppressing the wound prevented damage but the threat was already gone from the DB. Net result: no wounds, no rollover — half-broken.

## The actual rollover mechanism

`PENDING_CHALLENGER_THREAT` / `PENDING_DEFENDER_THREAT` globals (`Game.php:204-205`). `stDuelNewRound` (StatesTrait.php:1130-1144) reads these and adds them on top of the new round's starting threat, then deletes them. This is exactly the carry-forward channel.

Reference: `Maneuver_02039` (Add Threat) uses the same mechanism to add +1 threat to the *next* round to both sides.

## Fix

In `EventCharacterBeingWounded`:
- Detect the end-of-round threat→wound conversion by signature: `characterId == actor.Id && sourceId == adversary.Id` (this is the only wound event in the duel flow with that pairing).
- Capture `event->wounds` BEFORE zeroing it (that value IS the leftover threat amount, per StatesTrait.php:1472).
- Determine actor's side (challenger vs defender) via `getDuelChallengerId()`.
- Add the captured amount to `PENDING_CHALLENGER_THREAT` or `PENDING_DEFENDER_THREAT`.

Also tightened the "adversary absent" check: card says "unless your adversary is absent." Used `adversary->Location != actor->Location` in addition to `characterIsInDiscardOrLocker`. In practice the location check subsumes the destroyed check (destroyed characters have Location "Locker-…"/"Discard-…"), but both are cheap and explicit.

## Known limitation: lethality is lost

If the suppressed threat was lethal, the rolled-over threat is non-lethal. There is no `PENDING_<side>_THREAT_IS_LETHAL` global. `Maneuver_02039` has the same limitation. Card text doesn't mention lethality, so leaving as-is. If a future card needs lethality preservation, the right move is to add the global rather than special-case it here.

## Why not preserve via the DB row instead?

Could have intercepted before the SQL wipe — but the wipe happens in centralized state code, not in an event hub. Cards shouldn't be reaching back into `stDuelEndOfRound`. PENDING globals are the supported extension point.
