# Miyato and Ota (_02043) — Two Technique Implementation

## Card Text
- **Technique A:** "After Miyato and Ota perform a **Maneuver** • Copy the effects. Send that combat card to **The Locker**. Usable once per day."
- **Technique B:** "Choose a **Flourish** in your discard pile and place it on top of your deck."

## Implementation

### Technique A (Technique_02043a): Copy Maneuver + Locker

Auto-resolving technique, no custom state needed. Once per day (`ResetOnDuelEnd = false`, `ResetOnDayEnd = true`).

**WHY technique channel for copied combat values instead of re-firing EventDuelCalculateManeuverValues:**
`updateRoundWithCombatStats` in DB.php has a design where mode 'maneuver' recalculates ending threats using `combat_riposte + eventRiposte` (just the current event's values), but stores `maneuver_riposte = maneuver_riposte + eventRiposte` (cumulative). Re-firing the maneuver calculate event would leave the ending threats reflecting only ONE maneuver's worth even though the DB column accumulated both. Instead, we read the stored maneuver values from `duel_round` and add them through `EventDuelCalculateTechniqueValues`. Mode 'technique' correctly sums `combat + maneuver + technique` for the ending threat calculation, so the copy is properly reflected.

**WHY re-fire EventResolveManeuver separately:** The resolve event only does `setUsed(true)` (idempotent) and `INSERT INTO duel_round_maneuver` (logs the copy). It also broadcasts to the maneuver's own `handleEvent` for any special effects (card draw, stat changes, etc.). Critically, it does NOT call `updateRoundWithCombatStats`, so no double-counting issue.

**Flow:**
1. `EventResolveManeuver` fires during maneuver phase → technique captures maneuver ID, sets `$maneuverPerformed = true`
2. Player returns to action choice, sees this technique available
3. Player activates technique → `EventResolveTechnique` fires:
   - Reads maneuver values from `duel_round` DB
   - Re-fires `EventResolveManeuver` for special effects
   - Sends combat card to locker via `createCardSentToLockerEvent`
4. `EventDuelCalculateTechniqueValues` fires → adds the stored maneuver values through technique channel
5. `EventDuelNewRound` / `EventDuelEnd` → resets tracking state

**WHY combat card to locker during technique resolution (not end of round):** Moving the card from `LOCATION_DUELING_LINE` to the locker immediately is safe because combat card stats were already committed to `duel_round` when the card was announced. Round cleanup iterates `LOCATION_DUELING_LINE` cards and won't find this one, so no double-handling.

### Technique B (Technique_02043b): Flourish from Discard

State-based selection, follows Technique_02026a pattern exactly.

- `isAvailableToPlayer` gates on: in duel + at least one Flourish in controller's discard
- `getArgsFromTechnique` returns Flourish cards from discard as `cards` array
- `actFromTechniqueWithId` validates selection, uses `createCardAddedToFactionDeckEvent($playerId, $cardId, true)` (onTop = true)
- Default reset per duel (`ResetOnDuelEnd = true`)

**WHY `clienttranslate('Flourish')` in hasTrait check:** All trait strings on cards are wrapped in `clienttranslate()`. The `hasTrait` method compares against `ModifiedTraits` which stores the `clienttranslate`-wrapped values. Must match the same wrapping.

## Files Created
- `modules/php/cards/tac/techniques/Technique_02043a.php`
- `modules/php/cards/tac/techniques/Technique_02043b.php`
- `modules/php/States/tac/State_duelChooseTechnique02043b.php`

## Files Modified
- `modules/php/cards/tac/_02043.php` — added technique imports, instantiated both with `setId`, wired into `$this->Techniques`
- `modules/php/States.php` — added `DUEL_CHOOSE_TECHNIQUE_02043b = 521020432`
- `states.inc.php` — added `"02043b"` transition in `DUEL_CHOOSE_TECHNIQUE_EVENTS`
- `modules/js/OnUpdateActionButtons.tac.js` — added `duelChooseTechnique_02043b` handler for card selection buttons

## No JS State Handling Needed for Technique A
Technique A auto-resolves. No custom state, no JS handler needed.

## Pattern Notes
- Two techniques on one character follows `_02026` (Croc de Lion) pattern: `setId("Technique_02043a")` / `setId("Technique_02043b")` to disambiguate
- Character already implements `IHasTechniques` + `TechniqueTrait` via base `Character` class, so no interface/trait additions needed on `_02043`
