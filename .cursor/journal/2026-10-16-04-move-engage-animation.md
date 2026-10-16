# Move that engages should show the engage spin

## Bug
Basic Move (and any CardMoving with engage) sets Engaged inside EventCardMoved and sends one `cardMoved` notif. `notif_cardMoved` copied `args.engage` onto the card, then `createCard` added `._7sfs-engaged` on insert. The CSS transition on `._7sfs-card` (the same one `notif_cardEngaged` uses) never ran, so the character popped sideways at the destination.

`args.engage` is the card's Engaged flag AFTER the move, not "this move engaged them". An already-engaged character also has it true. EventHub only flips Engaged when `event->engage && !card->Engaged`, so the client signal for "this move engaged them" is: payload engaged, and `card.engaged` was false before we overwrite it.

## Fix
In `notif_cardMoved`, for a Character, animations on, newly engaged: create the destination card unrotated, then `animateEngageOnCard` adds the class after two frames and waits for the transform transition (600ms fallback). Already-engaged stays rotated through the slide. Animations off keeps the old instant class.

WHY after the slide, not on the sliding node: `slideAndAttach` transforms the wrapper; the engage rotate is on `_image`. Doing it on the replacement matches `notif_cardEngaged` and survives the destroy/recreate. Doing it on the old node would get cut off when we destroy it.

WHY two rAFs: class-on-insert has no from-state, so the transition is skipped. Same reason a flip's WAAPI fill used to hide a later engage.

Did not lengthen the `cardMoved` synchronous duration (1000). The handler already awaits the slide; awaiting the 0.5s rotate extends only the engaging case. Queue waits on the promise.
