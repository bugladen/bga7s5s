# Constanzo setupTable_01006 — thug not visible in hand

Eddie reported: on main, after choosing a Red Hand Thug at `setupTable_01006`, the chosen thug never visibly appears in the faction hand.

## What was wrong

Backend was fine. `_01006::actFromCardWithId` queues `EventCardAddedToHand`, which runs after `setupTable_01006_2` ack when we return to `SETUP_TABLE_EVENTS`. Card moves to hand in DB and `cardAddedToHand` fires.

The UI hid the hand during all `setupTable*` states (`Setup.js` `alwaysHiddenStates`), and `notif_cardAddedToHand` only called `addCardToDeck` — it never unhid `#factionHand-placeholder`. Contrast with `notif_drawCard` / `notif_factionResolveCardDraw`, which both unhide.

WHY this bites Constanzo specifically: his ability puts a card in hand *during setup*, before the normal panache draw. Plans Within Plans (`_01016`) uses the same add-to-hand event but during planning, when the hand is already visible — so it looked fine.

Adding into a `display:none` HandStock is also suspect — cards can end up in the stock with broken layout and stay invisible even after a later unhide when panache cards are drawn.

## Fix

1. `Notifications.js` `notif_cardAddedToHand`: unhide placeholder *before* `addCardToDeck`, then `checkFloatingHand`.
2. `Setup.js`: if `gamedatas.factionHand.length > 0`, show the hand even in early/setup states — covers refresh mid-Constanzo setup after the card is already in hand.

## Do not "fix"

Do not re-hide the hand during setupTable just because the normal day-1 draw hasn't happened yet. Constanzo is the deliberate exception.
