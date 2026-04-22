# Audit of _02024 (Oath of Vengeance)

Eddie asked me to audit `_02024` against its card text. Read through all relevant files:
- `_02024.php` (card definition)
- `State_duskPhaseBegin02024.php` (state definition)
- JS files for the state (OnEntering, OnLeaving, OnUpdateActionButtons .tac.js)
- `states.inc.php` transition mapping
- `States.php` constant definition
- `FrameworkActionsTrait.php` for `actFromCardWithLocations` routing
- `PlayerActions.js` for `submitLocations` / `onCityLocationsSelected`
- `EventFactory.php` for all event creation methods
- `Theah.php` for event processing and `getCityLocation`
- `DB.php` for card serialization (confirming `ReknownLocations` persists)
- Base `Card.php` and `Scheme.php` classes

## Card Text
> Add two Renown to this card.
> **Forced:** After your **Musketeer** destroys their adversary • Collect a Renown from this card.
> **Forced:** At the beginning of Dusk • Move each Renown on this card to different locations.

## Verdict: All correct, no fixes needed.

### Ability 1: Scheme Resolution — Add two Renown
- `EventResolveScheme` handler checks `$event->scheme->Id == $this->Id` 
- Fires `createReknownAddedToCardEvent($this->ControllerId, $this->Id, 2)` — adds exactly 2
- Notification message is clear

### Ability 2: Forced — Musketeer destroys adversary
- `EventCharacterDestroyed` handler
- Guards: `LOCATION_PLAYER_HOME` (scheme in play), `$inDuel` (adversary = duel context), `$this->Reknown > 0` (must have Renown to collect)
- Identifies which character is ours: `$challenger->ControllerId == $this->ControllerId`
- Checks `hasTrait("Musketeer")` on our character
- Checks `$myCharacter->Id != $event->characterId` — our musketeer is NOT the destroyed one (meaning the adversary was destroyed)
- Removes 1 from card via `createReknownRemovedFromCardEvent`, gives 1 to player via `createPlayerGainsReknownEvent`
- "Collect" = player gains it — correctly modeled

### Ability 3: Forced — Dusk phase move
- `EventDuskPhaseBegin` handler
- Guards: `LOCATION_PLAYER_HOME`, `$this->Reknown > 0`
- Creates one `EventTransition("02024")` per Renown on the card
- Each transition routes to `DUSK_PHASE_BEGIN_02024` state (via `states.inc.php` mapping)
- State is `ACTIVE_PLAYER` — player must choose a location (no pass/skip — correct for "Forced")
- `argsFromCard`: gets all city locations, filters out those already in `$this->ReknownLocations` — enforces "different locations"
- `actFromCardWithIds`: validates location exists, validates not already used, removes 1 from card, adds 1 to location, records location in `ReknownLocations`
- After action, `nextState("")` → loops back to `DUSK_PHASE_BEGIN_EVENTS` to process next transition
- `EventDuskPhaseEnd` clears `ReknownLocations` for next day cycle

### Routing verification
- JS: `onCityLocationsSelected()` → `submitLocations()` → defaults to `actFromCardWithLocations`
- PHP: `FrameworkActionsTrait::actFromCardWithLocations()` → gets sourceId from globals → gets card → calls `$card->actFromCardWithIds()` with location names
- `getCityLocation(string $name)` works by name, consistent with what's passed

### Serialization
- `ReknownLocations` is a public property on `_02024` — PHP `serialize()` captures it automatically
- `IsUpdated = true` is set whenever `ReknownLocations` changes → DB write triggered in event loop
- Card deserialized via `safeUnserialize()` preserves all properties between state transitions

### Edge cases considered
- Both duel characters controlled by same player: code picks challenger, which could miss defender-is-musketeer case. Practically irrelevant (you don't challenge your own characters).
- More Renown than city locations: impossible in practice (starts at 2, only goes down; 5+ city locations).
- Card removed from home mid-Dusk: transitions already queued, would still process. Harmless.
