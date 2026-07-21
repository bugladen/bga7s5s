# Noble Sacrifice (03017) — Scheme Implementation

## Card text

> Add a Renown to two different locations.
> ──
> **Reaction:** After your character at a **City** location is destroyed •
> Wound each opposing character at that location. Each of your characters
> at that location heals a wound. If the destroyed character was a
> **Zealot**, draw a card.

Eisen scheme. Initiative 53, PanacheModifier 0. Traits: Heroic, Finale
(both already in `TraitNames::$TraitsJson`).

## Files

- `modules/php/cards/faf/_03017.php` — scheme class. Queues the
  resolve-state transition; mirrors `_03006` (Premonition) shape.
- `modules/php/cards/faf/reactions/Reaction_03017.php` — the
  after-destroyed reaction.
- `modules/php/States/faf/State_planningPhaseResolveSchemes03017.php`
  — new GameState class for the two-location picker.
- `modules/php/States.php` — `PLANNING_PHASE_RESOLVE_SCHEMES_03017 = 2603017`.
- `states.inc.php` — `"03017" => ...` entry in the
  `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS.transitions` map.
- `modules/js/OnEnteringState.faf.js`,
  `modules/js/OnLeavingState.faf.js`,
  `modules/js/OnUpdateActionButtons.faf.js`,
  `modules/js/PlayerActions.js` — wire the two-location picker (clone of
  the 03006 wiring).

## Resolve clause — "Add a Renown to two different locations"

Verbatim copy of the Premonition pattern. `numberOfCityLocationsSelectable = 2`,
`actCityLocationsForReknownSelected` is the framework helper that
iterates the JSON array and queues one Renown event per location. The
JS enforces distinctness; the helper does the rest.

Why not call the helper directly from the scheme's `EventResolveScheme`
handler instead of via a sub-state? Because the player has to pick *which*
two locations — there's no deterministic mapping, so the sub-state is
unavoidable. Premonition does the same.

## Reaction — destroy-at-city → wound/heal/draw

### Trigger

`EventCharacterDestroyed` with:
- destroyed character's `ControllerId == owner.ControllerId` ("your character")
- destroyed character's `Location` is in city (via `locationInCity`, the
  canonical "city" check — Oles Inn / Docks / Forum / Bazaar / Governor's
  Garden)
- `isAvailable()` (once-per-day; base class resets on `EventDuskEndOfDay`)

### Why `$destroyed->Location` is reliable here

`EventCharacterDestroyed` is marked `runEventHubAfterCards = true` — card
handlers run BEFORE the hub does the actual move-to-locker. So at the
moment our `handleEvent` fires, the destroyed character's `Location` is
still the destroy-time location. Same pattern Reaction_01013 uses to
read `$character->Location` on the destroyed Red Hand.

### Context captured onto the reaction

- `$location` — destroy-time location (needed because by the time the
  player clicks the reaction button, the character is in the locker and
  its `Location` is no longer the city slot).
- `$destroyedWasZealot` — frozen at trigger time. The draw-a-card branch
  fires regardless of what happens at the location between trigger and
  click.
- `$destroyedName` — for prompt text only. Defensive null-check in
  `getReactionDescription` in case the persisted state is partial.

`$owner->IsUpdated = true` after each mutation so the field persists.

### Resolution effects (all three fire together on click)

The text has no "may" inside the bullet — once the player confirms
the reaction, all three sub-effects apply. They're queued in this order:
wounds, heals, (optional) draw. `getCharactersAtLocation($this->location)`
is queried at resolve time, not at trigger time, so any movement during
the queue between trigger and resolution is reflected — this is correct
per "at that location" being a deictic reference to the location, not a
snapshot of who was there when the death happened.

The two effects use `createCharacterBeingWoundedEvent` /
`createCharacterBeingHealedEvent` with `sourceId = $owner->Id` and
`abilityId = $this->Id` — same shape as Action_02018's wound-everyone
pattern.

### Pass behavior

Mirrors Reaction_03005 / Reaction_02004: declining clears `$location`
and friends but does NOT setUsed — the reaction stays available for
the next trigger that day. Only the `resolve` branch calls
`setUsed($theah, true)`.

### Scheme reaction lifecycle reminder

`Theah::buildCity()` loads scheme cards from every persistent location,
including discard. Schemes resolved into the discard pile during
Planning still receive `handleEvent` calls during High Drama, so the
reaction can fire on any destroy across the day — exactly what we want.

## Things considered

- **Should the reaction gate on the SCHEME being in play?** No. Schemes
  remain in the discard from Planning onward but their reactions stay
  active via `Theah::buildCity()` (verified during `_03005`
  implementation, journal `2026-05-17-01`). Don't add liveness guards.
- **Should "your character" exclude Leaders?** No reason to — Leaders
  are characters too, and the text doesn't carve them out.
- **What if the location has no opposing chars and no own chars left
  except the dead one?** Resolution still legal; the heal/wound loops
  just produce no events. The Zealot-draw still fires.
- **What if multiple of your characters die at the same location in a
  chain?** First one triggers the reaction; subsequent ones don't (Used
  flag set after first resolve). Per once-per-day rule, this is correct.

## Pre-commit hook compliance

- Reaction has both `$this->setUsed(` (line 124) and `$this->isAvailable(`
  (line 54). Verified by grep.
- No SchemeCityAction / Sorcerer / Risk patterns — N/A.
- Traits Heroic / Finale present in TraitNames lines 92, 77.

## Lint

`php -l` clean on all five touched PHP files.
