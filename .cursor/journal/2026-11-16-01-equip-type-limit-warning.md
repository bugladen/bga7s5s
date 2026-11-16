# Equip type-limit discard warning (feasibility)

## Question
Eddie asked: can we warn (like Crew Cap recruit) when equipping would push into `Reaction_AttachmentTypeLimit` (must discard a Weapon/Armor/Attire/OffHand)?

## Answer: yes
Same client pattern as `withCrewCapWarning` / `crewCapCheck` in `Utilities.js`.

## Data already on client
- Attachment: `traits`, `offHand` (from `Attachment::getPropertyArray`)
- Character: `attachments` (id list)
- Equip flow stores performer + chosen attachment in globals / client state args by pay confirm time

## Mirror of server rule
`Reaction_AttachmentTypeLimit`: after equip, if character has >1 non-OffHand Weapon/Armor/Attire of same type, OR >1 OffHand → reaction discard picker. OffHands do not count against type slots.

## Natural hook
`onPaymentConfirmed` for `highDramaEquipActionPayForAttachmentFromHand/Play` — wrap `bgaPerformAction('actHighDramaEquipAttachment')` in a confirm dialog when check fails.

Also need zero-cost / skip-pay paths and card-ability equips that land on same reaction if we want full coverage — basic HD equip is the main UX pain.

## WHY warning not block
Same WHY as the reaction itself (journal 2026-08-02-03): replace play is legal; warn then proceed so player can still choose which to keep after paying.

## Implemented (basic HD equip pay confirm)

Eddie said yes — wired for basic High Drama equip (hand + from play), including Let's Haggle / Smuggled Item pay states that share `onPaymentConfirmed` → `actHighDramaEquipAttachment`.

### Changes
1. **`Utilities.js`**: `attachmentTypeLimitWouldRequireDiscard(performerId, attachment)` + `withAttachmentTypeLimitWarning(callback, …)` — same confirm-dialog shape as crew cap. Rule mirrors `Reaction_AttachmentTypeLimit` (typed non-OffHand slots + separate OffHand limit).
2. **`PlayerActions.js` `onPaymentConfirmed`**: wrap equip action in the warning; brute pay unchanged.
3. **`OnEnteringState.js` hand pay**: set `clientStateArgs.performerId` (from-play already did). Fallback still reads `gamestate.args._private`.

### WHY not block
Replace play is legal; warning then proceed → post-equip discard picker unchanged.

### WHY no Smuggled Item special case
`_01187` is Artifact only — unequipping it does not free a Weapon/Armor/Attire/OffHand slot, so board state at confirm matches post-SI-discard type math.

### Not covered (by design for this pass)
Card-ability equips that never hit these pay states. Can extend later with the same helper.

### No PHP / no regression suite for this
Client-only UX. No `php tests/run.php` delta.

## Follow-up: specific type in message
Eddie asked for the type named. Renamed check → `attachmentTypeLimitConflict` returns `Weapon`/`Armor`/`Attire`/`Offhand` (translated) or null. Dialog: "Equipping this will require discarding a ${type}." via `dojo.string.substitute`.

## Bug: no warning on second Sabre (_04054) on Soline (_01089)
Used `performer.attachments` (server id[] from getPropertyArray). Client live state is `attachedCards` — `attachCard` / `notif_attachmentEquipped` push there and never sync `attachments`. After first equip, `attachments` was stale/empty → conflict always null.

Fix: prefer `attachedCards`; fall back to id[] → cardProperties only if attachedCards missing (fresh load before any client attach).
