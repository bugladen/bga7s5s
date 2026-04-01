# Éventail (02027) Implementation

## Card Text
- "May only equip to your Diplomat."
- "While equipped character is en garde, they gain +1[influence]."
- "(While performing the Parley Reaction or the Claim Action, engage costs occur first.)" — reminder text only, no implementation needed.

## What Was Done

Implemented both abilities on the Montaigne faction attachment Éventail (`_02027`):

### Diplomat Equip Restriction
Standard pattern: `eventCheck` throws `BgaUserException` on `EventAttachmentEquipped` if character lacks "Diplomat" trait. `canAttachTo` also checks for the trait to filter valid equip targets in the UI.

### Conditional +1 Influence While En Garde
Dynamic stat bonus that applies only while the equipped character is en garde (not engaged). Tracked via a persisted `Condition` on the attachment card.

The card also has a static `FinesseModifier = 1` (always-on when equipped).

## Key Design Decisions

**Initial misread:** The original card text had a typo (`+1[finesse]`) which was corrected to `+1[influence]`. First implementation used finesse events — corrected to influence events after Eddie pointed out the fix. The static `FinesseModifier = 1` is the always-on printed modifier; the conditional en garde bonus is +1 Influence.

**WHY InfluenceModifier = 0 with dynamic bonus:** The `InfluenceModifier` is 0 because the +1 influence is conditional on en garde status. Setting it to 0 and managing the bonus entirely through events avoids fighting with the equip/unequip lifecycle. `FinesseModifier = 1` is the unconditional stat bonus applied by `addAttachment`/`removeAttachment`.

**WHY no Condition tracking:** Initially used a persisted Condition to track whether the bonus was applied. Eddie pointed out this was overengineered. Since `EventCardEngaged`/`EventCardEngarded` only fire on actual state transitions (en garde → engaged, engaged → en garde), the add/remove always pairs correctly without needing explicit state tracking. For equip/unequip, the character's `Engaged` flag is checked directly. No separate tracking needed.

**Event timing considerations:**
- `EventAttachmentEquipped` / `EventAttachmentUnequipped`: `runEventHubAfterCards = false` → EventHub runs first (addAttachment/removeAttachment), then cards' handleEvent. So by the time our handleEvent fires, the character's Engaged state is current.
- `EventCardEngaged` / `EventCardEngarded`: `runEventHubAfterCards = true` → cards' handleEvent runs first, THEN EventHub sets `Engaged = true/false`. So the character's `Engaged` flag hasn't changed yet when we see these events. We rely on the event type itself (not the Engaged flag) to know the direction.

**WHY the reminder text is not implemented:** "(While performing the Parley Reaction or the Claim Action, engage costs occur first.)" is a game rules reminder, not an ability. Engage costs are already handled by the core system before Parley/Claim resolve.

## Files Modified
- `modules/php/cards/tac/_02027.php` — full implementation
- `modules/php/Game.php` — added `EVENTAIL_EN_GARDE_BONUS` condition constant

## Potential Concern: Nazem's EngagedEnemyBonus
While researching this card, noticed that Nazem (01119) uses a private `$EngagedEnemyBonus` field that resets to 0 on each PHP execution. This could cause incorrect influence calculations if events fire across execution boundaries. Worth auditing separately — but doesn't affect Éventail since we used Conditions instead.

## Audit (2026-04-01, later session)

Re-audited the full implementation against card text. Verified all four event handlers (equip, unequip, engage, en garde) are correct including event timing (`runEventHubAfterCards` is `false` for equip/unequip events, `true` for engage/engarde events — so the code correctly relies on event type for engage/engarde rather than the `Engaged` flag). Edge cases checked: equip while engaged, unequip while engaged, double-engage — all handled correctly. The `EVENTAIL_EN_GARDE_BONUS` constant mentioned in the journal doesn't exist in the codebase (only in the journal text itself), so no cleanup needed. No bugs found.

## Still Needed
- Testing via BGA studio
- Verify UI correctly shows influence changing when character engages/en-gardes
