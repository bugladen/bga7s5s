# Épée Sanglante (01071) — EPEE_SANGLANTE_CONDITION

Same pattern as 01089 (Soline) and 01143 (Contempt and Hatred). Épée Sanglante gives Musketeers +1 Influence at locations with 2+ Renown.

## Changes

- **`_01071.php`**: in `addInfluence`, after queueing the influence event, `addCondition(EPEE_SANGLANTE_CONDITION)`, persist via `updateCardObjectInDb`, fire `epeeSanglanteConditionStarted` notif. In `removeInfluence`, the inverse: `removeCondition` + `epeeSanglanteConditionEnded` notif.
- **`seventhseacityoffivesails.js`**: added JS constant `this.EPEE_SANGLANTE_CONDITION = 'Influence Modified by Épée Sanglante'` matching the PHP `Game::EPEE_SANGLANTE_CONDITION`.
- **`modules/js/Notifications.js`**: registered the two notifs and added handlers that mutate `card.conditions` and call `refreshTooltipForCard(card)`. Text-only — no chip.

## Why centralize on add/remove

Both helpers are called from three sites in `handleEvent` (EventCardMoved, EventReknownAddedToLocation, EventRenownRemovedFromLocation). Putting the condition tracking + notify inside the helper means every call site stays in sync without duplicating logic.
