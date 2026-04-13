# Until Morale Improves (01152) Audit

## Card Text
- Scheme, Initiative 30, Panache -2, Traits: Ad Hoc, Demoralize
- "Add a Renown to any location or move a Renown to an adjacent location."
- "City Action: Wound your performer • En garde target character at this location."
- "City Action: Wound your performer • Engage target character at this location."

## Files Reviewed
- `modules/php/cards/_7s5s/_01152.php` — scheme class + resolve logic
- `modules/php/cards/_7s5s/actions/Action_01152a.php` — en garde city action
- `modules/php/cards/_7s5s/actions/Action_01152b.php` — engage city action
- `modules/php/cards/actions/SchemeCityAction.php` — base class
- States in `States.php` and `states.7s5s.php`
- UI code in `OnEnteringState.7s5s.js` and `OnUpdateActionButtons.7s5s.js`

## Findings

**All clean — no fixes needed.**

### Scheme Resolve
3-state flow correctly models "add OR move":
- State 1: pick location to add renown, or pass
- State 2: pick source location with renown, or pass (skip both)
- State 3: pick adjacent destination

### City Action a (En Garde)
- Wounds performer, then en gardes an engaged target at performer's location
- Uses `createCardEngardedEvent` (the established spelling in codebase)
- Filters targets to engaged characters — correct for en garde (en garde applies to already-engaged characters)

### City Action b (Engage)
- Wounds performer, then engages an unengaged target at performer's location
- Filters targets to `! $character->Engaged` — correct

### Pre-commit compliance
Both actions call `createActionResolvedEvent()` and `$this->setUsed()`.

## Notes
- Neither action filters out the performer from the target list. Card text says "target character" without "another," so self-targeting is technically valid per card text. Consistent with not adding restrictions the card doesn't specify.
- "At this location" is checked via performer's location, which is equivalent to the scheme's location since the performer must be at the scheme to perform.
