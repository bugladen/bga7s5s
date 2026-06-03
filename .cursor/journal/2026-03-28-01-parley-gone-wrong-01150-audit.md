# Parley Gone Wrong (_01150) Audit

## Card Text
> Add a Renown to [The Forums]. Then, each opponent may move a Renown from any location to [The Forums].
>
> [BAR]
>
> Players can intervene in challenges at [The Forums] only if they added or moved a Renown there this Day. (Adding or moving a Renown during the Day counts.)

## Files Audited
- `modules/php/cards/_7s5s/_01150.php` (main class — Scheme)
- `modules/php/cards/Scheme.php` (parent class)
- `modules/php/EventFactory.php` (createRenownAddedToLocationEvent, createRenownRemovedFromLocationEvent)
- `modules/php/theah/events/EventReknownAddedToLocation.php` (event class)
- `modules/php/theah/events/EventCharacterIntervened.php` (event class)
- `modules/php/theah/EventHub.php` (event processing for both events)
- `modules/php/theah/Theah.php` (eventCheck dispatch)
- `modules/php/StatesTrait.php` (scheme lifecycle: approach cards played, scheme resolution)
- `states.7s5s.php` (PLANNING_PHASE_RESOLVE_SCHEMES_01150 state def)
- `modules/js/OnEnteringState.7s5s.js` (JS city location selection)
- `modules/js/OnUpdateActionButtons.7s5s.js` (confirm/pass buttons)
- `modules/js/OnLeavingState.7s5s.js` (cleanup)
- `modules/php/ZombieTrait.php` (zombie handling — nextState(""))

## Bug Found & Fixed

### OwnerId vs ControllerId (line 61)

The resolution handler used `$this->OwnerId` to skip the scheme owner when iterating opponents:
```php
if ($player['player_id'] == $this->OwnerId) continue;
```
But line 54 adds Renown for `$this->ControllerId`. If scheme control ever changes (owner != controller), the wrong player would be skipped — the owner gets excluded instead of the controller, while the controller gets offered to move Renown as an "opponent" of their own scheme.

Fixed to `$this->ControllerId`. This matches the pattern in `_01151` (line 156) which correctly uses `ControllerId` in the identical "skip controller from opponent loop" pattern.

## Analysis — Everything Else Is Correct

### Resolution Effect (Above the BAR)

**"Add a Renown to [The Forums]."**
- `EventResolveScheme` handler creates `EventReknownAddedToLocation` with `ControllerId`, `LOCATION_CITY_FORUM`, amount 1. ✅

**"Then, each opponent may move a Renown from any location to [The Forums]."**
- Iterates all players, skips controller, creates transition events to state 01150. ✅
- State is `activeplayer` type with `actFromCardWithLocations` and `actFromCardPass`. ✅
- `actFromCardWithIds` creates paired remove/add events (add has `$isMove = true`). ✅
- JS correctly excludes The Forum itself and locations with 0 Renown from selection. ✅
- "may" = optional: `actFromCardPass` lets opponents decline. ✅

### Ongoing Effect (Below the BAR)

**Intervene tracking:**
- Listens for ALL `EventReknownAddedToLocation` at `LOCATION_CITY_FORUM` while scheme is at `LOCATION_PLAYER_HOME`. ✅
- Tracks `playerId` in `interveneList` (skips `playerId == 0`). ✅
- Covers both adds and moves — a "move" fires an `EventReknownAddedToLocation` with `isMove = true`, which the handler doesn't distinguish from a plain add. Correct per card text. ✅

**Intervene restriction (`eventCheck`):**
- Triggers on `EventCharacterIntervened` while at `LOCATION_PLAYER_HOME`. ✅
- Uses `$oldTarget->Location == LOCATION_CITY_FORUM` to identify challenges at The Forums. Correct — the old target is the character already in the challenge, whose location determines where the challenge is. ✅
- Throws `BgaUserException` if intervening player not in `interveneList`. ✅

**Day boundary:**
- `EventDuskEndOfDay` resets `interveneList` to empty. ✅
- This means the restriction resets each Day, matching "this Day" in card text. ✅

**Scheme lifecycle timing:**
- Scheme moves to `LOCATION_PLAYER_HOME` during `stPlanningPhaseApproachCardsPlayed` (before resolution). ✅
- `EventResolveScheme` fires later in the same phase. ✅
- The `EventReknownAddedToLocation` from resolution is queued and fires while scheme is at home → controller gets added to intervene list. ✅
- Opponent transition events are queued after the Renown event (lower priority), so opponents' moves also trigger tracking. ✅

### Zombie handling
- Falls through to `$this->gamestate->nextState("")` — effectively passes. Correct for an optional action. ✅

## Verdict
One bug fixed (OwnerId → ControllerId). All other functionality correctly captures the card text.
