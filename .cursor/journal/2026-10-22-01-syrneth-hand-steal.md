# Syrneth Hand stolen by A Fine Addition

## Report
Syrneth Hand (`_01204`) was taken by A Fine Addition (`Action_04029`). Printed text: cannot be destroyed or moved from the equipped character. User: "cannot be moved" means cannot be stolen.

## Why the unequip eventCheck did not stop it
`_01204::eventCheck` throws on `EventAttachmentUnequipped` unless the host is dying. `queueEvent` catches that exception and only notifies — it does not abort the caller. `Action_04029` assigns `ControllerId` to the thief *before* queueing unequip, then keeps queueing discard → hand → pay. The unequip is dropped; the rest of the steal still runs.

Same shape as Kaspar's Panzerhand (journal 2026-08-28): steal flows never emit `EventAttachmentMoved`, so a move-event lock never sees them. The chooser predicate is `Attachment::canBeMoved()`.

## Fix
`_01204::canBeMoved()` returns false. `Action_04029` and Robbery already filter on it, so the act throws before the controller write. Dying-host unequip stays on the existing `eventCheck` (that path is destroy-on-death, not a steal).

Also gated Ratón `Technique_01096`. It steals with unequip+equip and previously only checked `canAttachTo` / equip restrictions, so it had the same hole. Discard-pile retrieval still does not use `canBeMoved` — "cannot be moved" applies while equipped.
