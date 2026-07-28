# notif_drawCard seal animation

## What

`notif_drawCard` FLIP-animates the drawn card from `{player_id}-score-seal` into the faction hand.

## Why (first attempt failed)

Tried bga-cards `addCard(card, { fromElement })`. Eddie saw no animation. Root cause: faction-hand cards get their background image only in `applyFactionHandCardStyle`, which runs *after* `addCard`. The library was sliding a transparent empty card. Also this codebase's working notifs (`notif_cardMoved`, `notif_approachCharacterPlayed`, etc.) use `animationManager.animationsActive()` + `slideAndAttach` / `element.animate`, not the stock `fromElement` path.

## Why this approach

FLIP on the already-styled hand card (add via `addCardToDeck` first, then animate from seal rect → settled rect). Matches the `element.animate` + delta pattern used elsewhere. Didn't use `slideAndAttach` like `notif_cardMoved` because there is no pre-existing source card DOM to move — only a seal — so we'd need a temp clone; FLIP on the real hand card is simpler and still gated the same way.

## Implementation notes

- Unhide + `checkFloatingHand` *before* add so destination rect is correct.
- Gate: `this.animationManager && this.animationManager.animationsActive()`.
- Uses `this.player_id` because `drawCard` is private to the drawing player.

## factionResolveCardDraw (same day)

Eddie asked for the same animation on panache/faction resolve draws. Same FLIP from seal; unhide before add; animate all drawn cards in parallel via `Promise.all` so multi-draw stays ~500ms (notif duration lowered 1000→500 to match). Sequential would stack waits for high Panache.

## Shared helper (same day)

Extracted `animateCardFromPlayerSeal(cardElement, playerId, options)` on Utilities.
Wired into: `notif_drawCard`, `notif_factionResolveCardDraw`, `notif_playLeader`,
`notif_approachSchemePlayed` (keeps sibling layout FLIP; replaces scale-grow),
`notif_approachCharacterPlayed`.

WHY helper: same seal→card math in five places; approach/leader use `args.player_id`
(public notifs) while draws use `this.player_id` (private).

## Not done / watch

- HandStock fan `transform` may briefly conflict with the WAAPI transform mid-animation; should snap back when WAAPI ends (no fill).
- `playLeader` still styles the seal *after* the FLIP — origin is the empty seal rect (intentional position anchor).

## cityCardAddedToLocation (same day)

Eddie wanted city cards to fly from `#city-ul-tower` the same way faction cards fly from seals.

WHY generalize instead of copy-paste: seal helper was seal-id-only; city origin is a fixed DOM id. Extracted `animateCardFromElement(cardElement, fromElement, options)` as the real FLIP math; `animateCardFromPlayerSeal` is now a thin wrapper resolving `{playerId}-score-seal`. City notif calls the generic with `$('city-ul-tower')`, duration 400 (matched prior scale-grow).

Replaced scale-from-zero in `notif_cityCardAddedToLocation` — same create-then-FLIP order as playLeader/approachCharacter.

## cardAddedToCityDiscardPile (same day)

Eddie: animate discarded city cards *to* `#city-discard`.

Added inverse helper `animateCardToElement` — WAAPI translate+scale toward dest, no reparent. WHY not `slideAndAttach` (used by cardMoved): `#city-discard` is a 36×24 absolute icon; attaching a full card would wreck layout. After FLIP, still destroy + clear divId as before.

Duration 400 to match the prior shrink-in-place.

## characterDestroyed (same day)

Eddie: animate destroyed characters to `#{playerId}-locker`.

Reused `animateCardToElement` toward `${args.playerId}-locker`. Kept the parallel width/margin collapse on the card container — WHY: characters live in horizontal city rows; without collapse the gap snaps shut only on destroy and looks jumpy. Still disables `_image` CSS transition so engaged rotation doesn't fight the FLIP.

## cardSentToLocker (same day)

Same treatment as characterDestroyed: FLIP to `${args.playerId}-locker` + width collapse. Shared pattern for any card sunk to a player's locker.
