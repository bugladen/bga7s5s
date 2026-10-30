# Crystal Eye (_01200) Day-5 empty Approach soft-lock

## Bug

Equip Crystal Eye on Day 5 when the opponent's Approach Deck is empty → Forced queues opponent → card chooser → chooseList empty, Confirm disabled, no Pass → soft-lock.

## Why it happened

Forced text assumes there is a card to choose. Approach Deck depletes across Days; Day 5 can be empty. Chooser states only allowed `actFromCardWithId` with a sole `""` transition into `_2` / EVENTS. No empty-deck gate on equip (unlike Cat's Embargo `_01098`, which skips Forced when every hand is empty).

## Fix (layered, Cat's Embargo shape)

1. **Equip:** if no opponent has Approach cards, notify + skip transition — never enter the chooser.
2. **Opponent args:** omit opponents with empty Approach; refuse pick of empty Approach (stale client).
3. **Pass escape:** `actFromCardPass` on all five phase copies of step 1 + `_2`.
   - Step 1: named transitions `opponentChosen` / `pass` — Pass must NOT use bare `""` (that would enter `_2` and soft-lock again). Pass only legal when every Approach is empty.
   - `_2`: sole `""` transition; Pass legal only when chosen Approach is empty (unsticks live tables already in `_2`).
4. **JS:** Pass button when `opponents.length === 0` / `cards.length === 0`.
5. **Zombie:** pick first legal opponent/card; else `actFromCardPass` (old `nextState("pass")` was never a `_2` transition).

Also dropped the bogus `getGameDeckObject($playerId)` arg (DeckTrait takes none).

## Do not "fix"

Do not allow Pass when Approach still has cards — Forced is mandatory when fulfillable.

## Tests

`Card_01200_Test`: skip on empty equip; args filter; Pass exits; Pass/choose refuse when cards remain. All 12 pass.
