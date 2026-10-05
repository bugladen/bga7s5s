# Mobile duel: faction hand above table

## Problem

On mobile during a duel, the faction hand sat underneath the duel table. Players had to scroll past the whole (often tall) duel table to pick combat cards.

## Cause

`notif_duelStarted` / Setup `inDuel` path always did:

```js
dojo.place('factionHand-placeholder', 'duel_wrapper', 'after');
```

That was fine on desktop because the hand floats. On mobile, floating is disabled (`isFactionHandMobile` / CSS `!important`), so the parked placeholder position is the real UI position — and "after" put it below the table.

## Fix

Centralized `placeFactionHandForDuel()` in Utilities.js:
- Mobile → place **before** `#duel_wrapper`
- Desktop → keep **after** (preserve floating-hand scroll-anchor behavior)

Call sites: Setup.js (page load mid-duel), Notifications.js `notif_duelStarted`. Also re-run after `swapFactionHandStockIfNeeded` when `inDuel`, so rotating / resizing across the breakpoint flips order.

Stash `challengingPlayerId` / `defendingPlayerId` onto `gamedatas` at duel start (and clear at duel end) so the helper can gate to participants without the notif args.

## WHY not always "before"

Desktop historically parked the placeholder after the duel block so the floating hand's scroll anchor stayed near the duel content. User only asked for mobile. Don't change desktop feel without a reason.

## Unfinished / QA

Playtest: enter duel on phone, confirm hand is above table; end duel, hand returns under choose_container; rotate landscape/portrait mid-duel and confirm order flips correctly.
