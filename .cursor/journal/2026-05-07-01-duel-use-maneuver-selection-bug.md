# duelUseManeuverFromCombatCard chosen card not visually selected

## Symptom
User reported: in `duelUseManeuverFromCombatCard`, the cardId from `args.args._private.cardId` is found in `factionHand`, but `factionHand.selectCard(card)` produces no visual selection.

## Root cause
State transition chain:
1. `duelChooseAction` OnEnteringState sets `factionHand.setSelectionMode('single')` (OnEnteringState.js:632).
2. Player picks combat card → `actDuelActionChooseCombatCard` → server transitions through `duelChooseActionEvents` → `duelUseManeuverFromCombatCard`.
3. **`OnLeavingState['duelChooseAction']` sets `setSelectionMode('none')`** (OnLeavingState.js:382).
4. OnEnteringState for `duelUseManeuverFromCombatCard` calls `selectCard(card)`.

bga-cards' `selectCard` is a no-op when selection mode is `'none'`. Lookup succeeds, the call just doesn't paint anything.

## Verification (things I confirmed before "fixing")
- Args wiring is fine. PHP returns `_private => active => cardId` (ArgumentsTrait.php:756-765); BGA unwraps `active` for the active player, so `args.args._private.cardId` is the correct path. Same pattern works for `chosenAttachmentId` etc. elsewhere in the file.
- Card is still in hand. `actDuelActionChooseCombatCard` (FrameworkActionsTrait.php:1375) sets `CHOSEN_CARD` but doesn't move the card — it stays in `LOCATION_HAND`. So `factionHand.getCards().find(...)` does return the card object (the user's `console.log` would have shown that).
- `selectCard` is only used in this one place in the entire JS — no working sibling pattern to copy.

## Fix
Set selection mode to `'single'` with an empty selectable-cards list, then programmatically select:

```js
const card = this.factionHand.getCards().find(c => c.id === args.args._private.cardId);
if (card) {
    this.factionHand.setSelectionMode('single', []);
    this.factionHand.selectCard(card);
}
```

WHY the empty selectable list: mode must be non-`'none'` for `selectCard` to paint, but we don't want the player to be able to click the card to deselect it (action choices in this state are made via action buttons; the hand is decorative). `selectCard` is a programmatic call that bypasses the selectable-cards filter, so it still sets the `bga-cards_selected-card` class. No card has the click handler hot.

`OnLeavingState` already cleans up via `unselectCard` on every selected item, so no cleanup change needed there.

Fallback if a future bga-cards version still allows click-to-deselect on programmatically selected cards: set `cardElement.style.pointerEvents = 'none'` after `selectCard`, with matching reset in `OnLeavingState`.

## Why not use `_7sfs-chosen` CSS class instead
Considered manually adding `_7sfs-chosen` (white drop-shadow) or `_7sfs-selected` (lime drop-shadow) classes directly to the cardElement. That works visually but diverges from the developer's clear intent (they wrote `selectCard`) and from how the unselect cleanup is structured. Keeping the bga-cards selection API as the source of truth is more consistent.

## Watch out
Any future state that does `setSelectionMode('none')` on its way out, then expects the next state's `selectCard` to "just work", will hit this same trap. The bga-cards selection API is mode-gated — programmatic selection still requires the mode to permit it.
