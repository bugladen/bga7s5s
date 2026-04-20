# Heroic Intervention (_02058) - Risk Reaction Implementation

## Card Text
**If your performer is a Hero or Knight, this card has -1 cost.**
**Reaction:** After a challenge is issued, engage your adjacent performer. Move them to that location and they intervene.

## Implementation

Risk card (Neutral, WealthCost=1) with a single RiskReaction and a conditional cost discount.

### Pattern Used
Followed `Reaction_02050` (RiskReaction with performer selection + pay flow) and `Reaction_02003` (intervention mechanics).

### Key Decisions

**Why skip `interventionCheck()`:** The standard `interventionCheck()` in Theah.php validates the character is (a) at the same location as the target, and (b) not engaged. Heroic Intervention explicitly engages the performer and moves them FROM an adjacent location, so both checks would fail. Instead, we do the intervention directly (swapping DUEL_DEFENDER condition, updating CHOSEN_TARGET global) and queue the engage/move events separately. We DO still check challenge type restrictions (Valeri Mikhailov, Torvo Espada, Legendary Reputation) manually.

**Why engage+move are queued events but intervention is direct:** Following the Reaction_02003 pattern, intervention game state (conditions, globals, CHALLENGE_ACCEPTED) is set directly in the handler. The engage and move events are queued so the EventHub properly handles the physical state changes (Location update, Engaged flag) and other cards can react to them. The intervention event (EventCharacterIntervened) is also queued for listeners.

**Event ordering in EventRiskReactionTriggered:** We queue engage → move → intervened events. The intervention state changes happen immediately (conditions/globals), but the physical card state changes (Location, Engaged) happen when the queued events process. This is fine because the duel flow won't proceed until all events drain.

**Cost discount via `getReactionFromHandDiscount`:** At pay time, `$this->PerformerId` is already set from `performReaction`. We check if that performer has Hero or Knight trait and apply +1 discount (reducing cost from 1 to 0).

**Adjacent performer validation:** Uses `getAdjacentCityLocations()` from the defender's location. Home is included by default (adjacent to all city locations). Performers must be non-engaged and pass `canIntervene()`.

### Files Created
- `modules/php/cards/tac/reactions/Reaction_02058.php` — RiskReaction class

### Files Modified
- `modules/php/cards/tac/_02058.php` — added IHasReactions, ReactionTrait, reaction instantiation
