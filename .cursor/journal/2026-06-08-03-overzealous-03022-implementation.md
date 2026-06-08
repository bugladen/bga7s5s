# Overzealous (Risk _03022) Implementation

## Card Details
- **Overzealous**: Eisen Risk (Riposte 0, Parry 0, Thrust 4) / WealthCost 1
- Traits: Flourish, Hubris, Faith, Zeal
- Text: **Maneuver:** Final Strike • En garde target character at this location. If your participant was a Zealot or Hunter, draw a card.
- **(Final Strike activates if your participant is destroyed the round this card is played.)**

## Mechanics Breakdown

### Final Strike Pattern
Maneuver that:
1. Engages the target character when resolved
2. Tracks the participant (`FinalStrikeParticipantId`) when maneuver resolves
3. On `EventCharacterDestroyed` of that participant (while `IN_DUEL`):
   - If participant was Zealot or Hunter trait: draw a card for the challenger
   - Announce the effect either way

### Reference Pattern
Modeled after `Maneuver_01082` (A Heroic End):
- Same "track participant, react on destroyed, only while in duel" flow
- Different side effects: _01082 adds 2 threat + gains Lethal, while _03022 engages target + draws card conditionally

## Implementation Details

### Maneuver_03022.php
- Extends `Maneuver` with `FinalStrikeParticipantId` field
- `isAvailableToPlayer()`: gates on non-destroyed adversary (mirrors _01082)
- `handleEvent()` branches:
  1. **EventResolveManeuver**: 
     - Saves `FinalStrikeParticipantId` for later tracking
     - Engages the target adversary via `createCardEngagedEvent` (not direct property write)
     - Calls `$owner->IsUpdated = true` to sync card state
  2. **EventCharacterDestroyed**: 
     - Activates only if destroyed character matches tracked participant
     - Only while `IN_DUEL` (not mid-setup)
     - Checks `hasTrait('Zealot') || hasTrait('Hunter')`
     - On match: queues `createCardDrawnEvent` for the challenger (duel round actor)
     - Announces effect with trait name or "not a Zealot or Hunter" variant
  3. **EventManeuverCanceled**: 
     - Clears `FinalStrikeParticipantId` and syncs state
  4. **EventDuelNewRound**: 
     - Clears tracked participant at end of duel round (for cleanup when Final Strike doesn't fire)

### Risk_03022.php Updates
- Added `IHasManeuvers` interface
- Added `ManeuverTrait`
- Imported `Maneuver_03022` in use statement
- Added `$this->Maneuvers` array instantiating `new Maneuver_03022()`

## Pre-Commit Hook Compliance
- ✓ Maneuver has `EventManeuverCanceled` handler
- ✓ All traits exist in `TraitNames::$TraitsJson` (Flourish, Hubris, Faith, Zeal, Zealot, Hunter)
- ✓ No interfaces mixing (not a targeting ability)
- ✓ PHP linting: no syntax errors

## Key Decisions

### Why queue `createCardEngagedEvent` instead of direct property write?
The EventFactory's `createCardEngagedEvent` ensures the framework's event hub processes the engagement with all side effects (notifications, state tracking, etc.). Direct property write bypasses framework machinery. Reference: Action_03021, Reaction_03019, etc. use the same pattern.

### Why check adversary not destroyed at `isAvailableToPlayer` time?
The maneuver can only activate when there's a valid target. If the adversary is already in discard/locker (dead), there's no one to engage and no duel to track — checking this gate prevents nonsensical availability. Mirrors `Maneuver_01082`.

### Why both `EventCharacterDestroyed` AND `EventDuelNewRound` handlers?
- `EventCharacterDestroyed` fires if the tracked participant dies during the duel round.
- `EventDuelNewRound` fires at the end of the round. If the participant survived the round, the maneuver's Final Strike effect never fires, so reset `FinalStrikeParticipantId = 0` for cleanup (otherwise the field persists with a stale id until the next maneuver resolve).

### Draw card performer: Why `getDuelChallengerId()` not `getDuelRoundActor()`?
The effect says "draw a card" — the challenger (the player whose card triggered Final Strike) draws, not necessarily the participant. In fact, if the actor is the participant (and gets destroyed), then the actor's controller draws the card. The `getDuelChallengerId()` gets the right player in all cases (whether actor or defender gets destroyed).

Actually, reviewing the text more carefully: "If your participant was a Zealot or Hunter, draw a card." The "your" refers to the round actor (the player with the active combat card). The `getDuelChallengerId()` call gets the player, so the performer drawing is correct. The notification uses `$game->getPlayerNameById($performerId)` to announce who drew.

## File Structure
- `/modules/php/cards/faf/maneuvers/Maneuver_03022.php` — new maneuver class
- `/modules/php/cards/faf/_03022.php` — updated Risk class with `IHasManeuvers`, imports, and `Maneuvers` array

## Similar Cards
- `_01082` (A Heroic End) — Final Strike with +2 threat, gain Lethal, no trait gate
- `Maneuver_01084` (Master of Valroux Style) — Duelist trait gate on `getDuelRoundActor()` (different from this card's Zealot/Hunter gate on destroyed participant)
