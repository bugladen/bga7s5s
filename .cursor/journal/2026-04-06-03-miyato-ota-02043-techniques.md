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
   - Finds original maneuver on combat card, creates clone, adds to Miyato/Ota
   - Queues `EventResolveManeuver` for clone's unique ID (activates clone's internal state)
   - Sets `$pendingLockerSend = true` and stores combat card ID (deferred)
4. Clone's `EventResolveManeuver` fires → clone sets `IsActive = true` (original unaffected — different ID)
5. `EventDuelCalculateTechniqueValues` fires → adds the stored maneuver values through technique channel
6. `EventDuelEndOfRound` fires:
   - Original maneuver fires delayed effect (e.g., +1 threat each for 02039)
   - Clone fires delayed effect independently (+1 threat each again)
   - Technique queues `createCardSentToLockerEvent`
7. `EventDuelNewRound` / `EventDuelEnd` → removes clone from Miyato/Ota, resets tracking state

**WHY two mechanisms needed — deferred locker AND maneuver clone:**

The original approach sent the combat card to the locker immediately during `EventResolveTechnique`. This broke `Maneuver_02039`'s delayed threat because cards in the locker are NOT loaded into `Theah::$cards`.

Simply deferring the locker send to `EventDuelEndOfRound` fixed the original maneuver firing — but only gave 1x delayed effect instead of the expected 2x ("Copy the effects" means the delayed effect should also be duplicated).

The fix uses BOTH mechanisms:

1. **Deferred locker send** — Combat card stays in the dueling line until `EventDuelEndOfRound`, so the original maneuver fires its delayed effect normally. An `EventDuelEnd` fallback covers edge cases.

2. **Maneuver clone on Miyato/Ota** — A fresh instance of the maneuver class is created via `new (get_class($originalManeuver))()`, registered on Miyato/Ota via `addManeuver(notify: false)`, and activated through its own `EventResolveManeuver`. Because `setOwnerId` generates `{ownerId}_{ClassId}`, the clone gets a unique ID (e.g., `43_Maneuver_02039`) that won't collide with the original (e.g., `100_Maneuver_02039`). The clone receives `EventDuelEndOfRound` through normal `Card::handleEvent` dispatch (which iterates `IHasManeuvers`) and fires its delayed effect independently.

**Result for Maneuver_02039:** Original fires at end-of-round (+1 each) + clone fires at end-of-round (+1 each) = +2 each. Combat values are doubled through the technique channel separately.

**Clone lifecycle:**
- Created during `EventResolveTechnique`
- Activated via queued `EventResolveManeuver` (unique clone ID)
- Fires at `EventDuelEndOfRound` (checks `$this->IsActive`, no ID filter)
- Cleaned up via `removeClonedManeuver()` at `EventDuelNewRound` or `EventDuelEnd`
- Also cleaned on `EventTechniqueCanceled`

**WHY _02043 now implements IHasManeuvers + ManeuverTrait:** The clone needs to be on a card in `Theah::$cards` to receive events. The Character card (Miyato/Ota) is always loaded. Adding `IHasManeuvers` follows the Katain (`_02011`) pattern exactly.

**WHY NOT fire EventDuelCalculateManeuverValues for the clone:** Combat values are already doubled through `EventDuelCalculateTechniqueValues` (technique channel). Firing the calculate event for the clone would triple-count: 1x original maneuver + 1x technique channel + 1x clone maneuver channel.

**Previous approaches (reverted):**
1. Immediate locker send — broke delayed effects entirely (card removed from active cards)
2. Deferred locker only (no clone) — delayed effect fired 1x instead of 2x
3. Re-firing EventResolveManeuver on original — only re-set IsActive (already true), single instance still fires once

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
