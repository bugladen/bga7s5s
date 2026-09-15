# Hand attachment equip fly animation

## Task
User wanted `notif_attachmentEquipped` to fly the card from hand to the equipped character when the attachment came from hand (same idea as the city-row equip fly).

## First attempt (broke)
Detected fromHand via `factionHand.getCards().some(c => c.id === attachment.id)`. No fly in playtest.

## Why no fly
1. **id type**: HandStock ids are often strings; notif `attachment.id` is a number. `===` fails. Same pitfall as `notif_updateRoundWithCombatStats` (uses `getCardElement` for that reason).
2. **Optimistic remove**: `onPaymentConfirmed` removed `chosenCardId` from hand after pay — so even with loose match the hand node could be gone. User also saw payment `cardDiscardedFromHand` notifs first (wealth cards), then equip pop — easy to read as "attachment left via discard."

## Fix
- PHP `EventHub` AttachmentEquipped: capture `$fromHand = Location == HAND` **before** `moveCard`, pass `fromHand` in notify args.
- JS: `fromHand = args.fromHand || getCardElement(attachment)`; fly from hand node, else seal; `removeCard` after fly.
- `PlayerActions.onPaymentConfirmed`: do **not** optimistic-remove chosen attachment on `actHighDramaEquipAttachment` — leave it for the equip notif fly. Payment cards still remove.

## Related
Continues city equip fly journals. Combat-card hand id comment in Notifications.js is the id-type precedent.

## Unfinished
Playtest: equip faction attachment from hand (own view) with and without wealth payment.
