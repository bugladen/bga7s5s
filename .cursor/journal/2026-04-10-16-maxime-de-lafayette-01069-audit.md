# Maxime de Lafayette (01069) Audit

## Card Text
"Maxime ignores wounds from Sorceries and Sorcerer abilities he performs. (Wound costs are considered paid.)"
"Sorcerer Action: Discard a card • Put target non-Unique attachment from your discard pile into your hand."

## Files Reviewed
- `modules/php/cards/_7s5s/_01069.php` - Character class with passive wound ignoring
- `modules/php/cards/_7s5s/actions/Action_01069.php` - Action implementation (3 states)
- `states.7s5s.php` (lines 1006-1043) - Three states: 01069 (discard), 01069_2 (attachment selection), 01069_3 (game state to execute)
- `modules/js/OnEnteringState.7s5s.js`, `OnUpdateActionButtons.7s5s.js`, `OnLeavingState.7s5s.js`, `EventHandlers.js` - JS handlers
- `modules/php/theah/EventHub.php` - Wound event flow (BeingWounded → Wounded)
- `modules/php/cards/Character.php` - Where wounds are actually applied (line 220)
- No reaction files exist for this card (none needed)

## Verdict: PASS - No bugs found

### Part 1: Passive wound ignoring

The passive intercepts `EventCharacterWounded` (not BeingWounded). This is correct because wound application happens in `Character::handleEvent` at `EventCharacterWounded` time (`$this->Wounds += $event->wounds`). By skipping `parent::handleEvent()`, the wound is never applied but the event still went through the BeingWounded pipeline, so the ability's other effects (move, draw, etc.) proceed unaffected. "(Wound costs are considered paid.)" is correctly modeled.

**Fixed: ControllerId scope too broad** - Changed `$source?->ControllerId == $this->ControllerId` to `in_array($event->sourceId, $this->Attachments)`. The old check matched any card controlled by the same player; "he performs" means the source must be Maxime himself or an attachment equipped on Maxime. Sorcery risk maneuvers in duels wound the adversary (not the performer), so attachment coverage is sufficient.

**Fixed: Dead code removed** - Removed `$source instanceof ISorcererAbility` else branch. No Card subclass implements ISorcererAbility (only Action/Maneuver classes do), so the check was always false. The `$source->hasTrait("Sorcery")` fallback at the end already handles Sorcery cards regardless of abilityId.

### Part 2: Sorcerer Action

Flow is correct:
1. **Sorcerer check**: `$maxime->HasTrait("Sorcerer")` in isAvailableToPlayer ✓
2. **Not a City Action** (unlike 01068): No cardInCity check needed, correct ✓
3. **Hand must have cards**: Checked ✓
4. **Discard pile must have non-Unique attachments**: Checked in both isAvailableToPlayer and getArgsFromAction ✓
5. **Discard a card**: State 01069 → player selects from faction hand, card moved to purgatory ✓
6. **Select attachment**: State 01069_2 → player selects non-Unique attachment from discard pile (purgatory card not visible in discard pile at this point - correct) ✓
7. **Execute**: State 01069_3 → discard event fires (purgatory → discard), then remove attachment from discard, add to hand ✓

Event ordering: SorcererAbilityStart → transition to 01069_3 → remove from discard → add to hand → SorcererAbilityPlayed → ActionResolved. The discard event is queued before SorcererAbilityStart, so the discarded card hits the discard pile first. No timing issue with the attachment recovery.

### Observation

State 01069_2 has `"back"` transition defined but JS doesn't add a Back button. This appears intentional - once you've committed a card to purgatory by confirming in state 01069, you're locked in. Same pattern seen in other two-step actions.
