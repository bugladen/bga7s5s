# Broken-Time (01077) — additional combat card stayed in hand

## The report

Eddie forwarded a player report: Broken-Time gave a second combat card that
round; the player chose Pull (01172) and "ended up keeping it in his hand."
The question attached was whether the UI is allowed to let the second card stay
in hand rather than forcing it to be played.

Card text is unambiguous — "**Play one** as an additional combat card and sink
the rest." Mandatory. So this is a bug, not a design choice, and the answer to
the literal question is "no, it shouldn't, and the reason it did is a missing
move."

## Root cause (server)

`Maneuver_01077::actFromManeuverWithId` stages the chosen card by queuing a
`CardAddedToHandEvent` and setting `NEXT_COMBAT_CARD`. Staging in hand is
deliberate — the downstream play path (`actDuelUseManeuverFromCombatCardDeclined`
/ `actDuelPayForManeuverFromCombatCard`) is written as "move it out of hand onto
the Dueling Line", so the card has to be *in* hand for that to make sense.

`stSetNextCombatCard` then forks:

- `useManeuver` → `DUEL_USE_MANEUVER_FROM_COMBAT_CARD`. Both exits from that
  state (`actDuelUseManeuverFromCombatCardDeclined`, and
  `actDuelPayForManeuverFromCombatCard` further down) call
  `moveCard(..., LOCATION_DUELING_LINE, ...)`. Fine.
- `applyCombatCardStats` → `stApplyCombatCardStats`, which **never moves the
  card**. It doesn't need to on any other path: `actDuelActionChooseCombatCard`
  and `actGambleCardChosen` both move the card to the Dueling Line themselves
  before transitioning there.

So the no-maneuver branch of the Broken-Time path is the one hole. Pull has
`IHasActions` and no maneuvers, so `hasManeuversAvailableToPlayer` is false and
it takes exactly that branch. The card gets a `duel_round_combat_card` row and
contributes Riposte/Parry/Thrust, but stays in `Hand`. `stDuelEnd` only
discards cards found in `LOCATION_DUELING_LINE`, so it survives the duel.

Any maneuver-less card would reproduce this; Pull is not special.

**Fix:** move to the Dueling Line in the `applyCombatCardStats` branch of
`stSetNextCombatCard`, mirroring what `actDuelActionChooseCombatCard` does.

WHY there and not at the top of `stApplyCombatCardStats`: that state is reached
from four different places, three of which have already moved the card, and one
of which (Roll the Bones gamble stats, `ROLL_THE_BONES_CARD_ID` set) must
deliberately *not* have its card played. A blanket move there would have to
re-derive all of that. `stSetNextCombatCard`'s `NEXT_COMBAT_CARD > 0` branch is
only ever entered from 01077, so it's the narrow, unambiguous spot.

## Second bug found while verifying (client)

`notif_updateRoundWithCombatStats` removed the played card from the faction hand
only in the `else` of `if (args.gambled)`. `gambled` comes from
`duel_round.gambled`, which is **per round, not per card** — set by
`actGambleCardChosen`. Broken-Time explicitly interacts with gambling (the
reporter's round had both), so in that round the extra combat card was flagged
gambled and the client skipped the hand removal, leaving it visible in hand even
though it had been played.

Replaced the flag test with a membership test
(`factionHand.getCards().some(...)`), same idiom already used around line 501 for
attachments. A genuinely gambled card was never in the hand stock, so the check
is a no-op there and behaviour is unchanged for the normal path. Kept the
gambled styling branch separate — it's about the duel-row card, not the hand.

## Deliberately not changed

`actDuelActionChooseCombatCard` and `actGambleCardChosen` both queue
`CombatCardAnnouncedEvent`; the Broken-Time path does not (it emits its own
bespoke notify inside the maneuver instead). Adding the announce event would be
"more correct" — the card *is* being played as a combat card — but it's a
reaction trigger (Reaction_02039 and friends hang off it), so wiring it in is a
behaviour change well beyond a stuck-card fix. Flagged for Eddie, not done.

## Follow-up: CombatCardAnnouncedEvent on the Broken-Time path

Eddie asked for it — the second combat card should announce the same way a
normal hand play does.

Done in `stSetNextCombatCard` rather than in `Maneuver_01077`. WHY: announce
belongs at the moment of play, not at the moment of staging. Queuing it in the
maneuver would fire it during `DUEL_RESOLVE_MANEUVER_EVENTS` (still resolving
Broken-Time's maneuver), which is the wrong semantic moment for reactions that
key off "a combat card was announced."

Mirror of `actDuelActionChooseCombatCard`:
1. queue `CombatCardAnnouncedEvent`
2. queue transition `useManeuver` or move-to-dueling-line + `applyCombatCardStats`
3. `nextState("announceCombatCard")` → `DUEL_COMBAT_CARD_EVENTS`

Reused `DUEL_COMBAT_CARD_EVENTS` (already has reaction/pay/useManeuver/
applyCombatCardStats transitions) instead of inventing a new events state.
Added `announceCombatCard` transition on `DUEL_SET_NEXT_COMBAT_CARD` in
`states.inc.php`. Left the old direct `useManeuver` / `applyCombatCardStats`
transitions in place — nothing else nextStates them by name from this state
anymore, but they're harmless and avoid a silent break if something else does.

This also means reactions like Reaction_02039 / 01135 / 01098 / 02017 can now
fire against Broken-Time's additional card, which is the behaviour change that
made us leave this alone in the first pass. Eddie explicitly wants that.

## Follow-up: first live test — card still visible in hand

Eddie tested. Server side is now correct (he refreshed and Pull was gone from
hand), but the card stayed visible in hand for the whole duel. He also
articulated the spec, which is worth writing down because it's not obvious:

> the card *should* be put in hand visually — that's needed in case there are
> Maneuvers on it — but it should be removed once (a) Maneuvers are chosen and
> paid for, or (b) there are no Maneuvers to choose from.

Good news: both (a) and (b) already converge on `stApplyCombatCardStats`.
Traced it to be sure —
`maneuverPaidFor` → `DUEL_APPLY_COMBAT_CARD_STATS`,
`maneuverDeclined` → `DUEL_APPLY_COMBAT_CARD_STATS`,
and the no-maneuver branch transitions there directly. So the single removal
point in `notif_updateRoundWithCombatStats` satisfies the whole spec; no new
notification is needed. I was tempted to emit an explicit `cardRemovedFromHand`
at each of the three server-side move sites and decided against it — it would
fire for normal combat-card plays too, changing when the card leaves hand
relative to the announce log for every duel, to fix a case the existing
notification already reaches.

**What I think actually broke it:** my own guard from the first pass.

```js
const cardInHand = this.factionHand.getCards().some(c => c.id === combatCard.id);
```

Strict `===` on `id`. The stock's cards can be seeded from `gamedatas.factionHand`
at page load *and* from notification args, and there's no guarantee those agree
on number-vs-string. If they don't, the guard silently reports "not in hand" and
skips the removal. Replaced with `this.factionHand.getCardElement(combatCard)`,
which resolves `factionhand-card-${card.id}` through the DOM — template
interpolation makes it type-insensitive, and it returns null for a genuinely
gambled card that was never in hand, which is the case the guard exists for.
`applyFactionHandCardStyle` and Setup.js already use `getCardElement` this way.

Honestly I should not have introduced that comparison at all. Every other hand
removal in this codebase (`notif_cardDiscardedFromHand`, `notif_cardRemovedFromHand`,
the whole of PlayerActions.js) just calls `removeCard` and lets the library's
own lookup decide. I invented a membership test because I was nervous about
calling `removeCard` on a gambled card that was never in the stock, and the test
I invented was more fragile than the thing I was protecting against.

Caveat I can't rule out from here: it's also possible Eddie's test ran without
the first JS change deployed at all, in which case the `else if (args.gambled)`
gating was still live and that alone explains it. Either way the current code
handles both. Need a second test to confirm.

