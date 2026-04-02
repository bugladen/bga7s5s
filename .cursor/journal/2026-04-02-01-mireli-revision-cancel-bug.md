# Mireli's Revision (01135) + Let The Sword Decide (01146) Cancel Bug

## The Bug

When Mireli's Revision maneuver was activated and the opponent chose "wound adversary," then Let The Sword Decide's cancel reaction fired and the player cancelled — the wound still happened.

## Root Cause: deleteManeuverEvents was too narrow

The event flow:

1. `stResolveManeuverFromCombatCard` queues: `EventManeuverActivated` (pri 3), `EventResolveManeuver` (pri 3), `EventDuelCalculateManeuverValues` (pri 3)
2. During `EventManeuverActivated` processing, both the maneuver and the reaction respond:
   - `Maneuver_01135` uses `stackEvent()` for its transition → gets priority = MIN(queue) - 1 = 2
   - `Reaction_01146b` uses `queueEvent()` with `HIGH_PRIORITY = 2`
3. The maneuver transition has a lower `event_id` (inserted first), so it fires first when both are at priority 2
4. Player enters choice state, picks wound → `actFromManeuverWithId` queues `EventCharacterBeingWounded` with `abilityId = maneuverId`
5. Back in events state, reaction transition fires → player cancels
6. `deleteManeuverEvents` only deleted `EventResolveManeuver` and `EventDuelCalculateManeuverValues` — the wound event was **not** covered
7. Wound event fires anyway

Secondary issue: `Maneuver_01135` sets `IsActive = true` and `ReduceThrustNextRound = true` in `actFromManeuverWithId` but never resets them on cancel. The -2 Thrust would persist to the next round.

## The Fix

### 1. Broadened `deleteManeuverEvents` (DB.php)

Changed from deleting only `EventResolveManeuver` and `EventDuelCalculateManeuverValues` to deleting ALL events containing the maneuverId. The maneuver ID format (`{cardId}_Maneuver_XXXXX`) is specific enough that false positives are essentially impossible. This catches wounds, transitions, and any other side effects queued by the maneuver.

WHY not just add `EventCharacterBeingWounded` to the list: Other maneuvers queue other side effects too (Maneuver_01079 queues unequip/discard events, Maneuver_01110 queues location events). The broad approach handles all of them. `deleteManeuverEvents` is always called BEFORE `EventManeuverCanceled` is queued, so the canceled event is never caught.

### 2. Added EventManeuverCanceled handler to Maneuver_01135

Resets `IsActive` and `ReduceThrustNextRound` to false. Without this, the -2 Thrust modification in `EventDuelCalculateCombatCardStats` would still apply in subsequent rounds despite the maneuver being "cancelled."

## WHY the maneuver fires before the reaction

Both the maneuver transition (`stackEvent`) and the reaction transition (`queueEvent` with HIGH_PRIORITY) end up at priority 2. But `stackEvent` inserts first (it's the first handler to run during card iteration), giving it the lower `event_id`. The SQL `ORDER BY event_priority LIMIT 1` breaks ties by row order, so the maneuver transition fires first.

This ordering is actually fine for the game flow — the player announces and chooses their maneuver option, then the opponent gets a chance to cancel. The fix ensures that cancellation properly cleans up everything the choice produced.

### 3. Broadened `deleteTechniqueEvents` (DB.php)

Same treatment as maneuvers — changed from deleting only `EventResolveTechnique` and `EventDuelCalculateTechniqueValues` to deleting ALL events containing the techniqueId.

### 4. Full audit of maneuvers with internal state variables

Audited every class in `cards/_7s5s/maneuvers/`. Added `EventManeuverCanceled` handlers to all 8 maneuvers that store private state:

| Maneuver | Variables reset | State set in choice vs resolve |
|---|---|---|
| **01135** | `IsActive`, `ReduceThrustNextRound` | Choice (`actFromManeuverWithId`) — **critical** |
| **01059** | `selectedLocation` | Choice (`actFromManeuverWithIds`) — **critical** |
| **01103** | `UseParry`, `UseThrust` | Choice (`actFromManeuverWithId`) — **critical** |
| **01164** | `MoveCharacter`, `MoveLocation` | Choice (`actFromManeuverWithIds`) — **critical** |
| **01052** | `HealAtEndOfRound` | Resolve (`EventResolveManeuver`) — defensive |
| **01082** | `FinalStrikeParticipantId` | Resolve (`EventResolveManeuver`) — defensive |
| **01107** | `WillDieFromWound`, `AdversaryId`, `AdversaryLocation` | Resolve (`EventResolveManeuver`) — defensive |
| **01114** | `IsActivated` | Resolve (`EventResolveManeuver`) — defensive |

"Critical" = state is set during the choice state (`actFromManeuverWithId`/`actFromManeuverWithIds`), which fires BEFORE the cancel reaction. Without the handler, the state persists and affects future rounds.

"Defensive" = state is set during `EventResolveManeuver` handling, which gets deleted from the queue before it fires. The handler is insurance against event ordering changes.

### 5. Full audit of techniques with internal state variables

Audited every class in `cards/_7s5s/techniques/`. Added `EventTechniqueCanceled` handlers to all 6 techniques that store private state:

| Technique | Variables reset | State set in choice vs resolve |
|---|---|---|
| **01036** | `MoveDaniela`, `MoveLocation` | Choice (`actFromTechniqueWithIds`) — **critical** |
| **01063Swap** | `swapId` | Choice (`actFromTechniqueWithId`) — **critical** |
| **01090** | `RevealedCardId`, `CardPlayerId` | Resolve (`EventResolveTechnique`) — defensive |
| **01096** | `IsActive`, `AdversaryWoundedThisRound`, `AdversaryId` | Resolve (`EventResolveTechnique`) — defensive |
| **01101** | `IsActivated` | Resolve (`EventResolveTechnique`) — defensive |
| **01063** | `IsActive`, `BastienWoundedThisRound` | Resolve (`EventResolveTechnique`) — defensive |

## Files Changed

- `modules/php/theah/DB.php` — broadened both `deleteManeuverEvents` and `deleteTechniqueEvents`
- `modules/php/cards/_7s5s/maneuvers/Maneuver_01135.php`
- `modules/php/cards/_7s5s/maneuvers/Maneuver_01059.php`
- `modules/php/cards/_7s5s/maneuvers/Maneuver_01103.php`
- `modules/php/cards/_7s5s/maneuvers/Maneuver_01164.php`
- `modules/php/cards/_7s5s/maneuvers/Maneuver_01052.php`
- `modules/php/cards/_7s5s/maneuvers/Maneuver_01082.php`
- `modules/php/cards/_7s5s/maneuvers/Maneuver_01107.php`
- `modules/php/cards/_7s5s/maneuvers/Maneuver_01114.php`
- `modules/php/cards/_7s5s/techniques/Technique_01096.php`
- `modules/php/cards/_7s5s/techniques/Technique_01090.php`
- `modules/php/cards/_7s5s/techniques/Technique_01036.php`
- `modules/php/cards/_7s5s/techniques/Technique_01101.php`
- `modules/php/cards/_7s5s/techniques/Technique_01063.php`
- `modules/php/cards/_7s5s/techniques/Technique_01063Swap.php`
