# I Know that Trick! (_01165) Audit

## Card Text
> Maneuver: Copy the effects of a Technique on the adversary or one of their equipped attachments.

Risk card. Riposte=1, Parry=1, Thrust=1, WealthCost=0. Traits: Flourish, Prepared. Implements `IHasManeuvers` with `Maneuver_01165`.

## Bug Found and Fixed

**Missing `IsUpdated` in `EventDuelEnd` handler.**

The `EventDuelNewRound` handler correctly marks the Risk card as updated after clearing `copiedTechniques`:
```php
$this->removeCopiedTechniques($event->theah);
$owner->IsUpdated = true;
```

But the `EventDuelEnd` handler didn't:
```php
if ($event instanceof EventDuelEnd)
{
    $this->removeCopiedTechniques($event->theah);
    // Missing: $owner->IsUpdated = true;
}
```

WHY this matters: If a duel ends abruptly (e.g., character killed mid-round) without going through `EventDuelNewRound` first, the `copiedTechniques` array gets cleared in memory but the Risk card isn't flagged for DB serialization. The stale `copiedTechniques` references could persist and be deserialized in a future duel, causing `removeCopiedTechniques` to attempt removal of techniques that no longer exist on any character. Harmless but wrong.

Fix: Added `$owner->IsUpdated = true` to the `EventDuelEnd` handler, matching the pattern in `EventDuelNewRound`.

## Verified Correct

**Technique sourcing**: `isAvailableToPlayer` and `getArgsFromManeuver` both check adversary directly AND all adversary attachments for techniques via `IHasTechniques`. Matches card text "on the adversary or one of their equipped attachments."

**Clone and ID uniqueness**: `clone $technique` + `$copy->setOwnerId($actor->Id)` produces a copy with a unique Id (`actorId_ClassId` vs original's `adversaryId_ClassId`). Event handlers only fire for matching Ids, so original and copy don't interfere.

**Event sequence**: Matches standard technique activation flow in `FrameworkActionsTrait`:
1. `EventTechniqueActivated` with `$copied = true` — prevents original from being marked used
2. `EventResolveTechnique` — technique resolves its effects
3. `EventDuelCalculateTechniqueValues` — combat value modifications apply

**`CHOSEN_TECHNIQUE_IS_MAIN = false`**: Correctly marks the copied technique as non-main, so `duel_round_technique` DB record has `technique_is_main = 0`.

**Cleanup**: Copied techniques removed from actor at `EventDuelNewRound` (per-round cleanup) and `EventDuelEnd` (safety net). The `removeCopiedTechniques` method removes from the owning character and marks that character as updated.

**State/JS**: State `DUEL_RESOLVE_MANEUVER_01165` uses `argsForStatePrivate` (private args to active player only). JS creates buttons for each available technique with `technique.Id` and `technique.Name`. Zombie handler correctly present with `nextState("")`.

## Design Notes
- `getTechniquesAvailableToPlayer` filters by technique-specific `isAvailableToPlayer` but NOT by `isAvailable()` (used status). This means you can copy an already-used technique — the copy starts fresh. Seems intentional for "copy the effects."
- The `$copied = true` flag on `EventTechniqueActivated` is only used in the EventHub to skip `setUsed` on the original. The copy still gets `setUsed(true)` from `Technique::handleEvent` base class, which is fine since the copy is temporary.
