# Nazem _01119 Dusk Influence Bug

## The Bug

Nazem ended up with 1 Influence after returning home at dusk when he should have had 0 (base influence). All characters should be disengaged at dusk, so the engaged-enemy bonus should be 0.

## Root Cause: Stale ModifiedInfluence

`updateInfluence` queues an `EventCharacterInfluenceModified` event that updates `ModifiedInfluence` when processed by the EventHub. But the event goes to the END of the queue and isn't processed until later. When multiple `updateInfluence` calls happen in rapid succession (e.g., multiple en garde events during dusk), the second call reads a stale `ModifiedInfluence`.

### Dusk Event Queue Structure

`stDuskPhaseCleanup` queues events per-character in a foreach loop:
1. Move-home event (EventCardMoving)
2. Disengage event (EventCardEngarded)

EventCardMoving creates EventCardMoved at the END of the queue when processed. So the processing order ends up:
- All EventCardMoving + EventCardEngarded events (interleaved per-character)
- All EventCardMoved events (pushed to the end)

The en garde events fire while characters are still at Nazem's location, which is correct. Each one triggers Nazem's EventCardEngarded handler.

### The Calculation Bug (2 engaged enemies)

State: EngagedEnemyBonus=2, ModifiedInfluence=2

1. **Engarded(E1)**: `updateInfluence(1)` → `2 - 2 + 1 = 1`. Queues InfluenceMod(2→1). Sets EngagedEnemyBonus=1.
2. **Engarded(E2)**: `updateInfluence(0)` → `2 - 1 + 0 = 1` ← **BUG**: ModifiedInfluence is STILL 2 (stale!), not 1. Should be `1 - 1 + 0 = 0`.

Both queued events set influence to 1. Final result: 1 instead of 0.

## Fix

Added `$this->ModifiedInfluence = max(0, $newInfluence)` in `updateInfluence`, right after calculating the new value and before queueing the event. This ensures subsequent calls within the same event processing cycle see the updated value.

The EventHub will also set `ModifiedInfluence` when it processes the queued event (to the same value for the last event), which is harmless. The key insight is that the LAST queued InfluenceMod event will set the correct final value, and intermediate EventHub processing between events is safe because the Moved handlers return early (EngagedEnemyBonus is already 0, so `newInfluence == ModifiedInfluence && EngagedEnemyBonus == count` → true).

### WHY `max(0, ...)` 

Matches the EventHub behavior: `$character->ModifiedInfluence = max(0, $event->NewInfluence)`. Without this, a negative intermediate value could cause incorrect calculations in subsequent calls.

## Notes

- This bug only manifests with 2+ engaged enemies at Nazem's location during dusk (or any situation where multiple rapid en garde events fire). With 1 enemy, the single updateInfluence call works correctly and subsequent calls return early.
- The stale-value pattern could theoretically affect other cards that queue influence modification events and call updateInfluence multiple times in the same event processing cycle. But Nazem is the only card with this pattern currently.
