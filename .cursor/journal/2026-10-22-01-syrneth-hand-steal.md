# Syrneth Hand stolen by A Fine Addition

## Report
Syrneth Hand (`_01204`) was taken by A Fine Addition (`Action_04029`). Printed text: cannot be destroyed or moved from the equipped character. User: "cannot be moved" means cannot be stolen.

## Why the unequip eventCheck did not stop it
`_01204::eventCheck` throws on `EventAttachmentUnequipped` unless the host is dying. `queueEvent` catches that exception and only notifies — it does not abort the caller. `Action_04029` assigns `ControllerId` to the thief *before* queueing unequip, then keeps queueing discard → hand → pay. The unequip is dropped; the rest of the steal still runs.

Same shape as Kaspar's Panzerhand (journal 2026-08-28): steal flows never emit `EventAttachmentMoved`, so a move-event lock never sees them. The chooser predicate is `Attachment::canBeMoved()`.

## Fix
`_01204::canBeMoved()` returns false. `Action_04029` and Robbery already filter on it, so the act throws before the controller write. Dying-host unequip stays on the existing `eventCheck` (that path is destroy-on-death, not a steal).

Also gated Ratón `Technique_01096`. It steals with unequip+equip and previously only checked `canAttachTo` / equip restrictions, so it had the same hole. Discard-pile retrieval still does not use `canBeMoved` — "cannot be moved" applies while equipped.

## Follow-up: +1 Combat stayed on both characters

User: the illegal steal already happened. Yevgeni kept +1 Combat and the new holder also got it. Normally the Hand cannot be moved, but if a move does happen the bonus has to follow the card.

Why both had it: `queueEvent` swallows the unequip throw, so `removeAttachment` never runs. The later `EventAttachmentEquipped` only `addAttachment`s onto the new host. `ModifiedCombat` is a stored delta, not rebuilt from the attachment list, so the old host's +1 stays forever. `EventAttachmentMoved` already removes then adds — that path was fine. The hole is unequip-dropped-then-equip.

Fix: `EventHub::releaseAttachmentStillOnOtherCharacters` runs at the start of equip. Any other character who still lists the attachment loses it (all four modifiers, same as unequip) and gets an `attachmentUnequipped` notif with `statCorrection`. The client updates chips and unattaches, but does not reparent the card onto the city row — the card is already in hand or about to equip, and reparenting would leave a duplicate.

`Theah::repairAttachmentModifiersOnFormerHosts` does the same strip silently on `buildCity` when `AttachedToId` already names the real holder. A table saved in the bad state heals on the next load. Do not notify from that path; gamedatas picks up the corrected character. Do not strip when `AttachedToId` is 0 — unequip clears that before `removeAttachment`, so 0 means the character list is still the authority.

`statCorrection` must stay. A normal unequip notif reparents the live node so a following discard can fly it. Doing that during equip plants a city-row copy next to the new host.

## What is safe to delete later

Only the load-time heal. Search `remove-after-deploy: Syrneth Hand former-host heal`. That marks `repairAttachmentModifiersOnFormerHosts`, its `buildCity` call, the `$notify` argument and silent `updateCardObject` block, and the "reload strips +1 Combat" test. Once those games have loaded (the heal persists) or ended, delete those sites.

Do not delete `releaseAttachmentStillOnOtherCharacters`, the equip call, or `statCorrection`. Syrneth Hand still rejects unequip and `queueEvent` still swallows it, so a future move that equips anyway still depends on the strip at equip time.
