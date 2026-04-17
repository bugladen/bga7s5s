# Concealed Flintlock (_02054)

## Card Summary
Neutral FactionAttachment. Offhand Weapon (Ranged/Pistol/Stealth). WealthCost 1. Riposte 2, Thrust 1.

Two abilities:
1. **Maneuver**: Equip this card to your participant from your dueling line. If they have 1[Combat] or less, draw a card.
2. **Technique**: Your adversary may suffer a wound. If they do not, +1[Parry].

## Design Decisions

### Maneuver: Free equip from dueling line
The maneuver equips the card from the dueling line to the participant character. I treated this as a free equip (discount = WealthCost, cost = 0) since the card was already committed to the dueling line. Compare with _01155 (Improvised Weapon) which explicitly says "ignoring all costs" - _02054 doesn't say that, but the maneuver effect implies free equip since there's no payment UI during duel maneuvers.

WHY free equip: Maneuver effects during duels are supposed to be quick/automatic. Requiring payment would need a separate pay state, which no other duel maneuver does for self-equip. The card was already "in play" in the dueling line.

The Combat check (`ModifiedCombat <= 1`) happens before the equip event processes, but since the card has CombatModifier = 0, equipping doesn't change Combat, so the timing is fine.

### Technique: Conditional Parry via adversary choice
The technique gives the adversary a choice: suffer a wound, or let the technique owner get +1 Parry.

WHY this works with event ordering: In `actDuelActionChooseTechnique`, the game queues EventResolveTechnique before EventDuelCalculateTechniqueValues. During resolve, the technique queues a TechniqueTransitionEvent (HIGHEST_PRIORITY) which inserts the adversary's choice state BETWEEN resolve and calculate. So by the time EventDuelCalculateTechniqueValues fires, the adversary has already made their choice, and the `$AdversarySufferedWound` flag is set correctly.

Used `createTechniqueTransitionEvent` with adversary's ControllerId so the adversary is the active player in the choice state.

The `$AdversarySufferedWound` flag is reset on EventTechniqueCanceled, EventDuelNewRound, and EventDuelEnd.

### Pattern References
- Technique state: follows State_duelChooseTechnique02006 pattern (class-based GameState)
- Button choice: follows duelResolveManeuver_02038 pattern (simple id-based buttons)
- Equip from dueling line: adapted from Maneuver_01113's createAttachmentEquippedEvent usage

## Files Created
- `modules/php/cards/tac/maneuvers/Maneuver_02054.php`
- `modules/php/cards/tac/techniques/Technique_02054.php`
- `modules/php/States/tac/State_duelChooseTechnique02054.php`

## Files Modified
- `modules/php/cards/tac/_02054.php` - Added IHasManeuvers, IHasTechniques, instantiated abilities
- `modules/php/States.php` - Added DUEL_CHOOSE_TECHNIQUE_02054 = 52102054
- `states.inc.php` - Added "02054" transition to DUEL_CHOOSE_TECHNIQUE_EVENTS
- `modules/js/OnUpdateActionButtons.tac.js` - Added technique choice buttons
