# Angeline Dèmone (03025) — Techniques & Continuous Reaction Implementation

## Changes Summary

### 1. Technique_03025a — Regular +1 Riposte
**File:** `modules/php/cards/faf/techniques/Technique_03025a.php`

Simple in-duel technique that adds +1 Riposte. Pattern: base `IN_DUEL` gate + actor identity check (owner must be the duel actor) + `EventDuelCalculateTechniqueValues` handler that mutates `$event->riposte += 1` directly (no `addRiposte` method exists on this event — it's plain int field).

**WHY this simple pattern:** Riposte is the canonical stat modifier for in-duel techniques. The handler runs per-technique-calculation, so Angeline's riposte bonus is included in the threat calculation alongside any combat card stats.

### 2. Technique_03025b — Gambling Technique for Relocation
**File:** `modules/php/cards/faf/techniques/Technique_03025b.php`

Gambling Technique that moves both participants to any city location. Pattern: `IN_DUEL` + `DUEL_GAMBLED` gate (actor must have gambled for combat card this round) + actor identity check.

**Event lifecycle:**
- `EventResolveTechnique`: queues a transition event (`createTransitionEvent(..., "03025b", ...)`) which routes to the location-picker state via `states.inc.php` mapping.
- `getArgsFromTechnique`: supplies `locationIds` (all 5 city locations: Oles Inn, Bordello, Cathedral, Docks, Market).
- `actFromTechniqueWithIds`: validates the chosen location, queues `createCardMovingEvent` for both actor and adversary to that location.

**WHY both participants:** The text says "Move both participants to any City location." Unlike Matushka's adjacent-only pattern, Angeline's allows any of the 5 city locations. The move events are queued but the duel continues (no `EventDuelEnd` — just the location change).

**WHY not ISorcererAbility:** The card text doesn't carry the "Sorcerer" keyword on the Technique, so no `createSorcererAbilityStartEvent`/`createSorcererAbilityPlayedEvent` needed. The trait is on the Leader herself, not on this ability.

### 3. State Class: State_duelChooseTechnique_03025b
**File:** `modules/php/States/faf/State_duelChooseTechnique_03025b.php`

Standard active-player state for location selection. Uses the framework's `actFromCardWithIds` dispatch (called as `actFromCardWithLocations` from JS, which passes location strings as ids). State has a single "" transition back to `DUEL_CHOOSE_TECHNIQUE_EVENTS`.

**State ID:** `521 + 03025 = 52103025 (DUEL_CHOOSE_TECHNIQUE_03025B)`. Stored in States.php.

### 4. States & Transitions
**States.php:** Added `DUEL_CHOOSE_TECHNIQUE_03025B = 52103025` in the FAF section.

**states.inc.php:** Added `"03025b" => States::DUEL_CHOOSE_TECHNIQUE_03025B` to the `DUEL_CHOOSE_TECHNIQUE_EVENTS.transitions` mapping. This enables the `createTransitionEvent("03025b", ...)` call to route to the location-picker state.

### 5. Updated Card Class (_03025.php)
- Added `implements IHasTechniques`
- Added `use TechniqueTrait`
- Instantiated both `Technique_03025a()` and `Technique_03025b()` in the constructor's `$this->Techniques` array

### 6. Continuous Reaction (Reaction_03025.php)
**Changed:** Removed the `$this->setUsed($game->theah, true)` call from the wound effect.

**WHY continuous:** The printed text "After Angeline moves to a City location, wound an engaged character opposing her" does NOT carry a daily or one-time gate. The Reaction should trigger every time Angeline moves to a new city location, as many times as that happens per game.

**Implementation:** By NOT calling `setUsed(true)`, the Reaction's `Used` flag stays false. The base `CardReaction::handleEvent` resets `Used = false` on `EventDuskEndOfDay` — a defensive measure, but since we never flip it to true, the reaction is always available for its trigger condition.

**WHY no setUsed(false) at scope boundary:** Unlike "continuous Actions" (which call `setUsed(false)` at `EventPlayerTurnEnd` to reset for the next turn), Reactions are naturally "continuous" by default if they never flip the `Used` flag. The daily reset from `EventDuskEndOfDay` in the base class is sufficient (and provides a safety boundary even though this Reaction isn't restricted to once per day).

### 7. JavaScript Wiring

**OnEnteringState.faf.js:** Added handler for `duelChooseTechnique_03025b` that:
- Sets `numberOfCityLocationsSelectable = 1`
- Makes each location in `locationIds` selectable via `makeCityLocationSelectable()`
- Stores `locationIds` in `clientStateArgs` for later cleanup

**OnUpdateActionButtons.faf.js:** Added button handler for `duelChooseTechnique_03025b` that:
- Adds a "Confirm Location" button calling `onCityLocationsSelected()`
- Disables the button initially (enabled when exactly 1 location is selected)

**OnLeavingState.faf.js:** Added cleanup handler for `duelChooseTechnique_03025b` that:
- Clears each location as selectable
- Resets `selectedCityLocations` and `numberOfCityLocationsSelectable`

## Design Decisions & WHY

### Technique separation into _03025a and _03025b
The card has two Techniques with very different mechanics:
- 03025a: Pure stat modifier (no state class, no JS wiring).
- 03025b: Interactive location picker (state class, JS wiring, transition events).

**WHY separate files:** Keeping them in separate Technique files makes the responsibility clear — each file does one thing. Both are instantiated on the Leader and wired via `TechniqueTrait`.

### Location picker via `actFromCardWithIds`
The Technique's `actFromTechniqueWithIds` receives an array of location strings (not card IDs). The framework's `actFromCardWithLocations` passes location data directly to `actFromCardWithIds` after JSON-decoding, so location strings flow through as the "ids" parameter.

**WHY this pattern:** Consistent with the framework's existing location-picker infrastructure. No special handling needed beyond what the state class and JS wiring provide.

### No Sorcerer events on the Gambling Technique
The Gambling Technique doesn't implement `ISorcererAbility` because the printed text doesn't use the "Sorcerer" keyword. The trait is on Angeline the Leader; this ability is just a regular Technique that happens to be Gambling-gated.

**WHY important:** Pre-commit hook enforces that `ISorcererAbility` implementers call both `createSorcererAbilityStartEvent` and `createSorcererAbilityPlayedEvent`. If the text doesn't print "Sorcerer," don't implement the interface — avoid false positives and unnecessary event noise.

### Continuous Reaction design
**Original journal note:** "Reaction implemented. Status: ✅"
**Update:** Changed from "one-time-per-move" (with `setUsed`) to "always available" (continuous).

The Reaction was originally wired with `setUsed(true)` after wounding. Testing the card text revealed no gate — "After Angeline moves" should trigger every move. Removing the `setUsed` call makes the Reaction fire every time Angeline moves to a city location, without a daily cooldown or per-location cooldown.

**WHY no explicit flag:** Unlike a Continuous Action that needs to re-enable itself at `EventPlayerTurnEnd`, a Continuous Reaction doesn't flip the `Used` flag to true in the first place. The base `CardReaction::handleEvent`'s `EventDuskEndOfDay` reset is defensive (in case something else sets `Used`), but the main mechanism is: never call `setUsed(true)` = always available for the trigger.

## Testing Checklist
- [ ] Angeline enters a duel with her Gambling Technique available (requires gambling).
- [ ] Selecting the Technique shows a location picker with 5 city locations.
- [ ] Both duel participants move to the selected location; duel continues.
- [ ] Regular +1 Riposte Technique fires in a standard duel (no gambling required).
- [ ] Reaction fires when Angeline moves to a city location and there's an engaged opposing character.
- [ ] Reaction continues to be available after firing (no daily/per-move cooldown).
- [ ] No pre-commit hook violations (checked Sorcerer event discipline, no forbidden patterns).
- [ ] JS state handlers don't spam console errors; button is correctly disabled until a location is selected.

## Files Modified
1. `modules/php/cards/faf/_03025.php` — Added IHasTechniques, TechniqueTrait, Technique instances
2. `modules/php/cards/faf/reactions/Reaction_03025.php` — Removed setUsed(true) call
3. `modules/php/cards/faf/techniques/Technique_03025a.php` — New file
4. `modules/php/cards/faf/techniques/Technique_03025b.php` — New file
5. `modules/php/States/faf/State_duelChooseTechnique_03025b.php` — New file
6. `modules/php/States.php` — Added DUEL_CHOOSE_TECHNIQUE_03025B constant
7. `states.inc.php` — Added "03025b" transition mapping
8. `modules/js/OnEnteringState.faf.js` — Added duelChooseTechnique_03025b handler
9. `modules/js/OnUpdateActionButtons.faf.js` — Added duelChooseTechnique_03025b button handler
10. `modules/js/OnLeavingState.faf.js` — Added duelChooseTechnique_03025b cleanup handler
