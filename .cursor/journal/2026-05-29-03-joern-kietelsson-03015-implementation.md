# Joern Kietelsson (03015) "Fury's Edge" — Implementation

## Card Text

- **Forced:** After Joern musters • Wound him.
- During Dusk, Joern has **-3 Resolve**. *(Characters are destroyed when their
  wounds equal their Resolve)*
- When Joern's challenge is refused, he heals a wound.

Stats: Resolve 8 / Combat 3 / Finesse 2 / Influence 1. Eisen.

## Files

- `modules/php/cards/faf/_03015.php` — all three abilities live on the Character
  itself; no separate Reaction/Technique/Maneuver files.

## Ability 1 — Forced muster wound

Hooked on **both** `EventCharacterMustered` AND `EventApproachCharacterPlayed`
with `characterId == $this->Id`. Queue a
`createCharacterBeingWoundedEvent($this->Id, $this->Id, 1, ..., $this->Id)`
(self-target, self-source, ability id = own card id). Pattern mirrors _01009
(Cirilo) which handles the same pair of events with a single OR-condition.
Wound resolves after the muster/approach event completes.

WHY both events: "Musters" colloquially covers any way a character enters
play, but the engine emits a different event when an Approach card puts a
character into play vs. the standard muster path. Hooking only on
`EventCharacterMustered` would silently skip Joern's Forced trigger when he
enters play via an Approach. Cirilo's pattern is the canonical precedent —
add Approach to any "after I muster" handler.

Self-wounds set `abilityId == $this->Id` so a future "block opponent's
ability" passive (e.g. Kaspar _03014) doesn't accidentally see a non-ability
threat conversion when it should see an ability. Source.ControllerId still ==
Kaspar.ControllerId in that scenario, so Kaspar wouldn't block anyway —
self-source flows through.

## Ability 2 — Dusk -3 Resolve (the load-bearing decision)

### Why a private property flag instead of a recompute

I considered three approaches:

1. **Recompute ModifiedResolve on every relevant event** (like _01119 does for
   Influence with `$EngagedEnemyBonus`). Rejected — Resolve isn't normally
   event-driven the way Influence is. The single trigger is a phase
   boundary, not a stream of board-state changes.
2. **Override `getResolvePressureValue` / inject during stat calculation.**
   No `EventCharacterCalculateResolve` exists (only `EventCharacterCombatModified`
   / `EventCharacterInfluenceModified` exist as visible stat events — Resolve
   isn't on that list). Cancelled.
3. **Directly mutate `ModifiedResolve` on `EventDuskPhaseBegin` and restore
   on `EventDuskEndOfDay`, gated by a private flag.** Chosen.

The flag (`$DuskResolvePenaltyApplied`) is essential because attachments also
mutate `ModifiedResolve` (Character::addAttachment line 166, removeAttachment
line 193). If we naively `-= 3` / `+= 3`, attachment churn during Dusk is
fine, but skipped/duplicated phase begins are not. A flag lets us idempotently
apply once per Dusk.

### Manual destruction check after Resolve drop

`Character::handleEvent` (line 256) only triggers destruction inside an
`EventCharacterWounded` handler. If Joern enters Dusk at Wounds=5 (or any
value >= 5), reducing Resolve to 5 should kill him, but no wound event will
fire. So I mirror the EventHub unequip-destruction pattern (EventHub.php ~251):

```php
if ($this->Wounds >= $this->ModifiedResolve && ! $this->IsDying)
{
    $this->IsDying = true;
    $this->unEquipAllAttachments($event->theah);
    $destroyEvent = EventFactory::createCharacterDestroyedEvent(...);
    $event->theah->queueEvent($destroyEvent);
}
```

This is the rules-correct outcome: "(Characters are destroyed when their
wounds equal their Resolve)" — the parenthetical reminder text makes clear
that the threshold check applies *whenever* it's crossed, not only on a wound
event.

### Why EventDuskEndOfDay for the restore (not DuskPhaseEnd)

The Dusk lifecycle (StatesTrait):

1. `stDuskPhaseBegin` → `EventDuskPhaseBegin`
2. `stDuskPhaseCleanup` (routes everyone home, discards uncontrolled CDs)
3. `stDuskPhaseDiscard` (hand discards)
4. `stDuskPhaseDiscardEvents` (purgatory → discard)
5. `stDuskPhaseEnd` → `EventDuskPhaseEnd`
6. `stDuskEndOfDay` → `EventDuskEndOfDay` (Brutes discarded, etc.)

"During Dusk" should cover every step in between. `EventDuskEndOfDay` is the
last event of the day — restoring there guarantees nothing in Dusk sees the
restored value early. `EventDuskPhaseEnd` would technically work too (Brute
discard at step 6 doesn't read Resolve), but EndOfDay is the strict latest
safe point.

### Restore unconditional on flag, not on `isControlled()`

If Joern dies during Dusk, `DuskResolvePenaltyApplied` is still true and the
EndOfDay restore still runs. That's intentional. The destroyed-character
object is in the Locker; restoring its in-memory `ModifiedResolve` is
harmless. WHY: prevents the edge case where a "return from Locker" mechanism
(some unknown future card) bypasses the constructor and leaves Joern stuck at
ModifiedResolve = 5. Re-instantiation already calls `resetCard()` and sets
ModifiedResolve = Resolve = 8 — the unconditional restore is a defense
against a rebirth path that doesn't re-instantiate.

## Ability 3 — Challenge refused heal

`EventChallengeRejected` with `$event->challengerId == $this->Id`. Queue a
`createCharacterBeingHealedEvent`. Pattern is the symmetric counterpart to
_01119 (Nazem), which handles the same event with `challengerId == $this->Id`
to engage the refuser.

GOTCHA noted but inapplicable: Nazem also checks `targetId` against
`!Engaged`; Joern has no such filter — even if the rejected target is already
engaged, he still heals.

## Pre-commit hook compliance

Joern is a plain Character — not `ISorcererAbility`, not an Action subclass,
not a Reaction. No required-call gates apply. All five Traits ("Villain",
"Pirate", "Berserker", "Spy", "Vesten") already exist in
`TraitNames::$TraitsJson` — verified.

## Things I considered and rejected

- **Triggering -3 Resolve via an `eventCheck` that intercepts a hypothetical
  `EventCharacterCalculateResolve`**: cleanest semantically, but no such
  event exists in the engine. Adding one for one card isn't worth the blast
  radius.
- **Skipping the destruction check on Dusk Resolve drop** ("just let the next
  wound event resolve it"): if Joern enters Dusk at Wounds==5, there may be
  no further wound event before he heals/end-of-day; he'd survive a phase
  that should have killed him. The reminder text makes it explicit:
  destruction triggers on the threshold being met, not only on wounding.
- **Tracking the penalty via condition (`addCondition`)** instead of a
  private bool: conditions are intended for game-mechanic state visible to
  other cards; a one-card private bookkeeping flag is purely internal. Bool
  is right.

## What I'd flag for a future audit

- The Forced wound uses `abilityId = $this->Id` (his card id). If another
  card filters "events whose abilityId belongs to a Reaction/Technique/
  Maneuver" assuming abilityId-space is disjoint from character ids, that
  assumption is wrong here. None of the current filters do that, but worth
  noting.
- The unconditional restore at EventDuskEndOfDay is defensive. If
  re-instantiation is guaranteed on every return-from-Locker, the
  conditional restore (only if `isControlled()`) would be slightly cleaner.
  Couldn't find a counterexample either way, so I went defensive.
