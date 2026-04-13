# Reputation Meritee (01072) Audit

## Card Text
- **Scheme Resolve:** Add a Renown to a location with no Renown.
- **Leader City Action:** Engage your performer - Pressure with [Influence] counting only non-Mercenary characters. You succeed even if tied. If successful, discard a City Card and muster at that location. (Muster is a character from Approach Deck.)

## Bug Found & Fixed

### Missing Character type check on muster selection (Action_01072.php)
In `actFromActionWithId` for state `HIGH_DRAMA_PLAYER_TURN_01072_2`, when a player selects a card to muster (`$id != 0`), the filter only checked `$card->Id == $id` without verifying `$card instanceof Character`. The JS client-side correctly prevents selecting Scheme cards (marks them `_7sfs-unselectable`), but the server accepted any card ID from the approach deck. A manipulated client could muster a Scheme.

**Fix:** Added `&& $card instanceof Character` to the filter on line 185.

## Things Reviewed (No Issues)

### Scheme Resolve
- `_01072::handleEvent` transitions on `EventResolveScheme` to the planning phase state
- `actFromCardWithIds` validates location has 0 renown, creates renown event
- `actFromCardPass` correctly blocks passing when valid locations exist
- JS `OnEnteringState` only makes 0-renown locations selectable

### Leader City Action (Action_01072)
- `isAvailableToPlayer`: checks leader not engaged + in city + not already used
- `getPerformersForAction`: correctly adds leader as performer
- `handleEvent`: engages leader, sets `REPUTATION_MERITEE_PRESSURE_TYPE` flag, initiates Influence pressure
- Pressure mechanics in UtilitiesTrait: filters out Mercenary characters (line 567), handles tie-wins (line 654)
- State flow: pressure → choose city card (01072) → choose muster card (01072_2) → resolve
- City card discard: correctly gets uncontrolled ICityDeckCards at leader's location
- Muster: creates `CharacterMusteredEvent` at leader's location
- "None" buttons: properly gated — city card "None" only when no cards available, muster "None" only when no characters in approach deck
- Back button: allows re-selecting city card from state 01072_2

### Minor Observations (Not Fixed)
- `HIGH_DRAMA_PLAYER_TURN_01072_3` state constant defined in States.php but unused in state machine. ZombieTrait references `highDramaPhase01072_3` — dead code, probably from a past refactor. Not harmful.
- Missing semicolon on OnUpdateActionButtons.7s5s.js line 446 (cosmetic, ASI handles it).
