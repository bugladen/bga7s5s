# _05Gary Forced: draw two then flip

## Context
User updated `$this->Text` Forced line to: **When Rena has four or more wounds • Draw two cards, then Flip her.** (was Flip only). Art `05Gary.v5.jpg` still shows Flip-only + retain reminder — Text in PHP is source of truth for this change (user edited it).

## What I did
In `_05Gary::handleEvent` on `EventCharacterWounded` at Wounds ≥ 4:
1. Announce Forced
2. Queue two `createCardDrawnEvent` (Cesca / 04cd07 shape)
3. Then `flipToBSide` (existing same-Id Leader→Leader swap)

## WHY queue then sync flip
Printed order is Draw → Flip. Draws must go through EventHub (`playerDrawCard` + notifs). Flip is a same-Id class swap that has to finish before later handlers keep seeing A-side — keep it sync like before. Draw notifs therefore land after the flip notif in the client log; game effect (hand + B-side) is correct. Deferred PendingFlip-after-draw-counter was considered for strict notif order and rejected as fragile (any other draw for that player could steal a decrement).

## Tests
`tests/regression/cad/Card_05Gary_Test.php` — threshold flip+2 draws; below-threshold no-op.

## Not touched
Reaction / setup / B-side. Art reminder about retaining attachments was not in the user's Text edit.
