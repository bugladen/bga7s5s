# Ivy (_02042) — Passive +1 Gamble Reveal + Sorcerer City Reaction

## Card Text
- **Passive:** When your participant at this location gambles, they reveal an additional card.
- **Sorcerer City Reaction:** After your participant at this location reveals their gambled cards — Put a revealed Sorcery into your hand. *(Before choosing one as a combat card.)*

## Implementation

### Ability 1: Passive +1 Reveal

Override `getNumberOfGambleCardsToReveal` on the Character class itself (not on a sub-ability). Pattern from Sarafina (`_01010`), but with a key difference: Sarafina checks `$actor->Id == $this->Id` (only herself). Ivy checks `$actor->ControllerId == $this->ControllerId && $actor->Location == $this->Location && cardInCity($this)` — grants the bonus to *any* friendly participant at Ivy's location.

### Ability 2: Sorcerer City Reaction (Pre-Choice Timing)

This was the complex part. The card text explicitly says "(Before choosing one as a combat card.)" — the reaction fires *between* gamble card reveal and gamble card selection.

**WHY new intermediate states instead of using existing post-choice reaction window:**
The existing `DUEL_CHOOSE_GAMBLE_CARD_EVENTS` fires AFTER the player picks a combat card. Eddie confirmed the pre-choice approach. Functionally, pre-choice means the player takes a Sorcery first, then picks a combat card from the remaining revealed cards. Post-choice would mean choosing combat card first then recovering a sunk Sorcery — same net result in many cases, but different player agency (pre-choice lets you decide whether to keep the Sorcery or use it as combat).

**Architecture: 4 new game states inserted before DUEL_CHOOSE_GAMBLE_CARD:**
1. `DUEL_GAMBLE_REVEALED` (game) — `stDuelGambleRevealed`: peeks at top N cards, fires `EventDuelGambleCardsRevealed`, transitions to events
2. `DUEL_GAMBLE_REVEALED_EVENTS` (game) — `stRunEvents`: processes the reveal event + any reaction transitions
3. `DUEL_GAMBLE_REVEALED_REACTIONS` (activeplayer) — `playerReaction`: presents Sorcery choice
4. `DUEL_GAMBLE_REVEALED_PAY_FOR_REACTION` (activeplayer) — `playerPayForReaction`: pay state

All 4 entry points to `DUEL_CHOOSE_GAMBLE_CARD` were redirected through the new setup state:
- `DUEL_CHOOSE_ACTION` → `chooseGambleCard` (normal gamble)
- `DUEL_COMBAT_CARD_EVENTS` → `01135` (forced gamble from Reaction_01135)
- `DUEL_SET_NEXT_COMBAT_CARD` → `rollTheBones` (Roll the Bones maneuver)
- `DUEL_CHOOSE_GAMBLE_CARD_EVENTS` → `01135` (forced re-gamble)

**WHY redirect ALL paths, not just the normal gamble:** Ivy's reaction should fire on every gamble regardless of how it was triggered. The card text says "when your participant gambles" with no qualifier.

**WHY the setup state fires the event (not the caller):** Multiple entry paths (actDuelActionGamble, stSetNextCombatCard, Reaction_01135 transitions) would each need to peek at deck cards and create the event. Centralizing in `stDuelGambleRevealed` means the event firing logic exists in one place.

**WHY decrementing GAMBLE_REVEAL_COUNT in performReaction:** After taking a Sorcery from the deck to hand, the deck top shifts. `argsDuelChooseGambleCard` reads `GAMBLE_REVEAL_COUNT` cards from the deck top. Decrementing by 1 ensures it shows exactly the remaining revealed cards (the Sorcery is no longer in the deck).

**WHY no "back" button changes:** The back button in `DUEL_CHOOSE_GAMBLE_CARD` only renders when `args._private.cards.length == 0` (empty deck edge case). When cards are revealed normally, there's no back button, so no suppression needed.

### Reaction Button Tooltips

Eddie requested hover text on the Sorcery buttons so the player can identify the cards. The PHP reaction sends each card's `getPropertyArray()` as a `card` field on the button. The JS `playerReaction` handler checks for `button.card` and adds a tippy tooltip:
- User pref `== 2` (text mode): `createTextTooltipForRisk` — table with name, stats, traits, text
- Otherwise: card image tooltip

Sorcery only appears on Risk cards at this point, so no type-branching needed — always uses `createTextTooltipForRisk` for the text path. Eddie specifically asked that the tooltip respect the user's game preference for image vs text hover.

The reaction description includes "(hover for details)" as a brief hint.

## Files

### New
- `modules/php/theah/events/EventDuelGambleCardsRevealed.php` — event with actorId, playerId, revealedCardIds
- `modules/php/cards/tac/reactions/Reaction_02042.php` — Sorcerer City Reaction

### Modified
- `modules/php/cards/tac/_02042.php` — IHasReactions, ReactionTrait, wired Reaction_02042, override getNumberOfGambleCardsToReveal
- `modules/php/theah/Events.php` — added DuelGambleCardsRevealed constant
- `modules/php/EventFactory.php` — added createDuelGambleCardsRevealedEvent
- `modules/php/theah/EventHub.php` — added no-op handler case for EventDuelGambleCardsRevealed
- `modules/php/States.php` — 4 new state constants (5273, 52730, 52731, 52732)
- `states.inc.php` — 4 new state definitions, redirected 4 entry transitions
- `modules/php/StatesTrait.php` — added stDuelGambleRevealed
- `modules/js/OnUpdateActionButtons.js` — added tooltip support in playerReaction handler

### No JS state handling needed
The new states use existing state names (`playerReaction`, `playerPayForReaction`) that the JS already handles. The setup state (`duelGambleRevealed`) is a game state with no UI. No `OnEnteringState` / `OnLeavingState` changes needed.
