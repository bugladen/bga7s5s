# Matushka's Efficiency (01133) Full Audit

## Card Text
**Sorcerer Action:** You may engage your performer. if you do, ignore all costs • Move target character you control from this location to any other one.
**Sorcerer Maneuver:** Move both participants to an adjacent location. (The duel continues.)

## What Checked Out

### Sorcerer Action
- Sorcerer performer requirement: `isAvailableToPlayer` and `getPerformersForAction` both filter by `hasTrait("Sorcerer")` ✓
- "from this location" = performer's location: `isValidTargetForAbility` checks `$character->Location == $performer->Location` ✓
- "you control" = same controller: `$character->ControllerId == $performer->ControllerId` ✓
- "any other one" = all city locations except current, plus player home ✓
- Move execution: single `CardMovingEvent` with `eventCheck` in game auto-state 01133_3 (fixed in previous session, see `2026-04-07-02`) ✓
- Sorcerer events: start event queued in `actFromActionWithIds`, played event queued in `stateFromAction` ✓

### Sorcerer Maneuver
- Sorcerer actor check: `$actor->hasTrait("Sorcerer")` in `isAvailableToPlayer` ✓
- Adjacent locations: `getAdjacentCityLocations($actor->Location, $includeHome = false)` ✓
- Both participants moved: actor and adversary both get CardMovingEvents ✓
- Duel continues: state transitions to `DUEL_RESOLVE_MANEUVER_EVENTS` ✓
- No `eventCheck` on maneuver move events: consistent with ALL other maneuver implementations ✓

### Engage/Discount Mechanism
- Reaction_01133 fires on `EventEnteringPayState` for this card, only if performer not already engaged ✓
- Presents engage/pass buttons ✓
- Engage sets `WillEngage = true` and queues `CardEngagedEvent` ✓
- `getActionFromHandDiscount` adds `WealthCost` as discount when `WillEngage` is true ✓

## Bug Fixed: Stale WillEngage Flag

### The Problem
Cards persist via PHP `serialize()`/`unserialize()` — the constructor doesn't re-run on load. If `WillEngage` was set to `true` during a previous use, it stays `true` in the serialized state. If the card is reused and either:
- The performer is already engaged (reaction doesn't fire), or
- The player chooses "Pass" (reaction doesn't change `WillEngage`)

...the stale `true` value gives the cost discount without earning it.

### WHY This Matters
Even though Risk cards typically go to discard after use, the game has effects that can return cards to hand. Defensive coding is important here because a stale boolean silently gives free plays — hard to notice, hard to debug.

### The Fix
Reset `WillEngage = false` in `Reaction_01133::handleEvent` when `EventEnteringPayState` fires for this card, BEFORE the engage/pass choice is presented. This ensures:
- Fresh start every time the card enters pay state
- Pass correctly leaves WillEngage as false
- Engage explicitly sets it to true
- Already-engaged performer case: WillEngage reset to false, no reaction fires, no discount — correct

### WHY in handleEvent, Not Elsewhere
- Can't reset in constructor: doesn't run on unserialize
- Can't reset in `resetCard()`: only called in constructors, not during gameplay
- Can't reset in Action_01133's `EventActionTriggered` handler: too late, discount already calculated during pay state
- `EventEnteringPayState` is the right moment: fires just before cost calculation, before the player's choice
