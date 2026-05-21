# pickDecks: replace per-deck action buttons with a single Confirm

## Change

User asked: for state `pickDecks`, drop the `actPickDeck{deckId}-btn` row (one button per available deck) and replace with a single `Confirm` action button that behaves exactly like the in-modal `btnDeckSelect`.

- `modules/js/OnUpdateActionButtons.js`: removed the `args.availableDecks.forEach(...)` loop that added per-deck buttons. Replaced with `addActionButton('actConfirmDeck', _('Confirm'), () => this.deckPickerDeckSelected())` followed by `dojo.addClass('actConfirmDeck', 'disabled')` so it starts disabled (same initial state as `btnDeckSelect`, which is rendered `disabled` in the template).
- `modules/js/Utilities.js`: in `deckPickerShowTab`, when a tab is selected (`tabIndex === i`) also `dojo.removeClass('actConfirmDeck', 'disabled')`; on the `tabIndex === 0` reset, `dojo.addClass('actConfirmDeck', 'disabled')`. Mirrors the enable/disable that already happens for `btnDeckSelect`.

## Why this approach

`btnDeckSelect` already calls `gameui.deckPickerDeckSelected()` which reads the selected tab and dispatches `onStarterDeckSelected(id)` → `bgaPerformAction('actPickDeck', ...)`. So "behaves exactly like btnDeckSelect" maps cleanly to wiring the action button's callback to `this.deckPickerDeckSelected()`. No backend change needed — the `actPickDeck` PHP action still receives `deck_type`/`deck_id`/`deck_json` from `PlayerActions.onStarterDeckSelected`.

Kept the in-modal `btnDeckSelect` intact — the user only asked to remove the action buttons (the per-faction quick-select row above the modal), not the confirm button inside the picker. Both buttons now route to the same `deckPickerDeckSelected()` path.

## Timing note

`onUpdateActionButtons` adds `actConfirmDeck` before the modal is `dojo.place`'d, so by the time any tab handler in the modal fires, the action button element exists. Safe to call `dojo.addClass/removeClass('actConfirmDeck', ...)` from `deckPickerShowTab`.
