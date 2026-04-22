# Croc de Lion (02026) Duelist Technique Wiring

## What Was Done

Wired up both "Duelist Technique" abilities on the Montaigne offhand weapon "Croc de Lion" (`_02026`):

### Technique A: Engage adversary attachment
> "Duelist Technique: Engage target attachment equipped to the adversary."

Single state: `DUEL_CHOOSE_TECHNIQUE_02026a` (521020261) — Player picks an unengaged attachment on the adversary to engage.

### Technique B: Destroy engaged adversary attachment
> "Duelist Technique: Destroy target engaged attachment equipped to the adversary."

Single state: `DUEL_CHOOSE_TECHNIQUE_02026b` (521020262) — Player picks an engaged attachment on the adversary to destroy (unequip + discard from play).

Both use `EventResolveTechnique` to trigger a transition to their target-picking state, following the same pattern as `Technique_02011` (Katain DeWinter's engage ranged weapon technique).

## Key Design Decisions

**WHY IHasTechniques over IHasActions:** The card text says "Duelist Technique" — these are technique abilities that activate during duels, not general actions. They follow the Technique pattern (available only when IN_DUEL, resolved via `EventResolveTechnique`), not the Action pattern.

**WHY no Duelist equip restriction:** Initially added a Duelist-only equip restriction (eventCheck + canAttachTo) based on the "Duelist Technique" keyword, following the Mastercrafted Rapier (01074) pattern. Eddie corrected this — the card text doesn't say "May only equip to your Duelist", so no restriction should exist. "Duelist Technique" only describes the ability type, not an equip constraint. Lesson: only enforce restrictions explicitly stated in card text.

**WHY a/b suffix pattern for technique IDs:** Two techniques on one card, same approach as `HIGH_DRAMA_PLAYER_TURN_01152a/b` (Until Morale Improves). State constants end in `1` and `2` respectively (`521020261`, `521020262`). Technique IDs are `Technique_02026a` and `Technique_02026b`.

**WHY destroy pattern matches Action_01174:** The destroy technique uses the same unequip + discard-from-play sequence as `Action_01174` (Shoddy Craftsmanship's destroy action). First `createAttachmentUnequippedEvent`, then `createCardDiscardedFromPlayEvent` with `$asEffect = true`.

**WHY no `EventDuelCalculateTechniqueValues` handler:** Unlike simple stat-modifier techniques (e.g., +1 Riposte), these techniques have side effects (engage/destroy) rather than stat modifications. They only need `EventResolveTechnique` to trigger target selection.

## Files Created
- `modules/php/cards/tac/techniques/Technique_02026a.php`
- `modules/php/cards/tac/techniques/Technique_02026b.php`
- `modules/php/States/tac/State_duelChooseTechnique02026a.php`
- `modules/php/States/tac/State_duelChooseTechnique02026b.php`

## Files Modified
- `modules/php/cards/tac/_02026.php` — added IHasTechniques, TechniqueTrait, eventCheck (Duelist-only equip), canAttachTo, instantiated both techniques
- `modules/php/States.php` — 2 new state constants
- `states.inc.php` — 2 transition entries in DUEL_CHOOSE_TECHNIQUE_EVENTS hub
- `modules/js/OnUpdateActionButtons.tac.js` — 2 new state button handlers (same pattern as 02011)

## Still Needed
- Testing via BGA studio
- Audit against card text after testing
- ~~Confirm with Eddie whether Duelist-only equip restriction is correct for this card~~ — confirmed NO restriction, removed
