# Point of Opportunity (01189) Audit

## Card Text
> Traits: Duress, Fortune
> **City Action:** Engage your performer • Move a Renown from this location to an adjacent one, or from an adjacent location to this one. Discard this card.

## Verdict: No Bugs Found

### Two-action split
The "or" in the card text is implemented as two separate actions — standard pattern for this codebase:
- **Action_01189a**: "Move Renown from Adjacent Location" (FROM adjacent TO here)
- **Action_01189b**: "Move Renown to Adjacent Location" (FROM here TO adjacent)

### Availability checks
- 01189a: Checks adjacent locations have renown > 0, and an unengaged character exists here ✓
- 01189b: Checks this location has renown > 0, and an unengaged character exists here ✓

### Args filtering
- 01189a filters `getArgsFromAction` to only show adjacent locations with renown > 0 ✓
- 01189b shows all adjacent locations (no filtering needed since destination has no renown requirement) ✓

### Execution flow (both actions identical structure)
1. Engage performer
2. Remove 1 renown from source
3. Add 1 renown to destination (`$isMove = true`)
4. Discard this card (`$asEffect = true`)
5. Queue action-resolved event
6. Announce, reset pass count, transition

### Server-side validation
Both actions validate adjacency via `in_array($location->Name, $locations)` and validate renown availability at the source location. ✓

### Minor style note
`getArgsFromAction` uses `$performer->Location` for adjacency while `actFromActionWithIds` uses `$poo->Location`. Always the same value since `EventCityAction::getPerformersForAction` enforces performer is at card's location. Not a bug, just inconsistent reference.
