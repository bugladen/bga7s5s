# Duel: Technique or Maneuver after combat card (hub choice)

## Problem

Playing a combat card immediately forced `DUEL_USE_MANEUVER_FROM_COMBAT_CARD`. Technique only appeared after that pipeline finished. Rules: after a combat card is played, the player may choose a Technique **or** the Maneuver on that card (up to one of each per round, any order).

## Approach / WHY

**Commit the combat card at play time**, then return to `duelChooseAction` with both Technique and Maneuver as hub options.

WHY commit-on-play instead of an inline "Technique / Maneuver / Neither" prompt:
- Hub already existed for Technique after a card was on the line.
- Avoid inventing a new combined chooser state.
- Tradeoff: playing a combat card is no longer cancelable via Back at the maneuver prompt. Eddie accepted that (hand selection + Combat Card button is enough confirm).

WHY `DUEL_PENDING_MANEUVER_CARD` instead of reusing `CHOSEN_CARD`:
- `CHOSEN_CARD` is reused by unrelated duel actions/reactions. Maneuver choice can happen later, after other things have overwritten it.

WHY not gate on `duel_round_maneuver` row count:
- Miyato/Ota technique copies insert rows there too; that would falsely block the real combat-card Maneuver.

WHY Maneuvers only from the pending combat card (not character/attachment):
- Eddie clarified: Maneuvers only exist on cards played from hand. In-play Characters never have them. FactionAttachments may carry Maneuvers only when played from hand as combat cards. The old "Character Maneuver" hub button was a mistaken design.

## Broken Time — FINAL: auto-play kept (staging attempt reverted)

**Do not re-introduce staged/hub play for Broken Time.** Two attempts were made; the second was reverted at Eddie's direction.

Final behavior: `Maneuver_01077` sets `NEXT_COMBAT_CARD` (unchanged from the original design). `stSetNextCombatCard` announces the card, moves it to the Dueling Line, sets `DUEL_PENDING_MANEUVER_CARD` to the **new** card, and queues `applyCombatCardStats`. Only after stats does the player land back on the hub, where the new card's own Maneuver (and Technique, if unused) is offered.

WHY: Eddie's rule — *playing the card to the duel line is atomic to the Maneuver*. If the player returns to the hub between resolving Broken Time's Maneuver and playing the additional card, they can slip a Technique in between. That is not legal. The staging design (below) inherently created that window, which is why it had to go.

The staging design that was tried and removed: `DUEL_STAGED_COMBAT_CARDS` / `DUEL_EXTRA_COMBAT_CARDS` globals, hub play of the owed card, End Round gate, payment ban, duel-end safety sweep, and client hand restriction. All of that only existed to police a window that should never have opened. Auto-play makes the enforcement machinery unnecessary: the card can't be kept, can't be spent as Wealth, and can't be skipped, because the player never holds it as a decision.

Also unnecessary now: the `combatCardAvailable` allowance math. Back to `$combatCardsCount == 0`, since the extra card is never played from the hub.

The one piece worth keeping from the staging work is `DUEL_PENDING_MANEUVER_CARD` re-arming — that is how Broken Time still yields two Maneuvers in a round under the hub model.

## Files touched

- `Game.php` — new globals
- `FrameworkActionsTrait.php` — commit at play, `actDuelActionChooseManeuver`, pay/decline changes
- `ArgumentsTrait.php` — hub availability
- `StatesTrait.php` — apply/resolve/set-next/new-round/duel-end
- `Maneuver_01077.php` — `NEXT_COMBAT_CARD` + `ABNORMAL_FLOW`, and (finally) no hand transit: `addCardToWorld` + `parkCard`
- `states.inc.php`, `State_duelChooseGambleCard_03047.php` — transitions
- JS: hub button rename, duel-row highlight for maneuver/pay

## Follow-up: staged card was not restricted (first live test)

Eddie played Broken Time, drew 01088 into hand, and the hub let him play **any** hand card as the second combat card. Two gaps:

- `actDuelActionChooseCombatCard` never checked `DUEL_STAGED_COMBAT_CARDS`.
- `duelChooseAction` client handler called `factionHand.setSelectionMode('single')` across the whole hand.

Fixed by exposing `stagedCombatCardIds` in `argsChooseDuelAction`, restricting `setSelectionMode('single', stagedCards)` when non-empty, and throwing server-side if the chosen card is not the staged one.

### What the OLD behavior actually was

Worth recording because Eddie remembered it as "the card went straight to the combat line." Close but not exact: `Maneuver_01077` always queued `CardAddedToHandEvent` (staging in hand was deliberate — the old maneuver-decline/pay path moved it *out of hand* onto the line). Then `stSetNextCombatCard` **auto-played** it: announce → Dueling Line → stats, with the client removing it from hand at `notif_updateRoundWithCombatStats`. So the player never selected it; it only transited hand internally.

The new flow made that selection explicit, which is why the missing restriction was visible at all. The alternative flagged here — auto-play plus re-arming `DUEL_PENDING_MANEUVER_CARD` — is what Eddie then asked for, and is now the implementation. Restricting the hand selection was treating the symptom; the real defect was that the hub was reachable at all mid-Maneuver.

Honestly I should have caught this from the card text. "Play one as an additional combat card" is a single continuous effect of the Maneuver, not a permission to play one later. I read "mandatory" and reached for enforcement instead of asking whether the player should ever get the turn back.

## Follow-up: card added to hand by notification, never removed by one (third report)

Eddie, after the auto-play restore: the second combat card is added to hand by a notification and nothing ever removes it; a page refresh shows it is not really in hand. So the server side is right and the client hand stock is stale.

Emitted an explicit `cardRemovedFromHand` in `stSetNextCombatCard` right after the `moveCard` to the Dueling Line. Empty message so no extra log line.

WHY this instead of a third patch to `notif_updateRoundWithCombatStats`: that handler's removal is a side effect nested inside `if (args.mode == 'combat')` → `if (!args.statsAddedToExistingCombatCard)` → `if (player_id == controllerId && getCardElement(...))`. Two previous sessions patched the innermost condition (2026-08-08: the `args.gambled` gate, then the `getCards().some(id ===)` guard) and it is still broken. Guessing at a fourth condition is not debugging. The add is an explicit notification, so the removal should be one too — that is the idiom everywhere else in this codebase (`notif_cardRemovedFromHand` just calls `removeCard` with no guards).

The 2026-08-08 session explicitly considered and rejected emitting `cardRemovedFromHand`, on the grounds that it would fire for normal combat-card plays too and shift when the card leaves hand for every duel. That objection does not apply to emitting it *only* in the Broken-Time branch of `stSetNextCombatCard`. Normal plays are untouched and still rely on the stats-notification removal.

Left the removal in `notif_updateRoundWithCombatStats` alone — it is the removal point for normal plays. For the Broken-Time card it now runs second and no-ops (`getCardElement` returns null once removed).

### FINAL: stop routing the card through hand at all (fourth report)

The explicit `cardRemovedFromHand` did not fix it either. Eddie reported the same symptom again: card enters hand by notification, lands on the duel line, stays in hand, refresh proves the server never had it there.

At that point I had patched the removal three times (gambled gate → `getCards()` guard → explicit notification) and each patch was a guess about why a removal silently no-ops. I re-read every link in the chain and could not find the broken one — and the Brute muster in `EventHub` emits the exact same `cardRemovedFromHand` with an empty message and works. So either the build under test lacked the change, or there is a client-side condition I cannot see by reading. Both are unfalsifiable from here.

So I deleted the problem instead of the symptom: **the card no longer enters the hand.** `Maneuver_01077` now calls `addCardToWorld` + `parkCard($id)` instead of queueing `EventCardAddedToHand`; `stSetNextCombatCard` moves it Purgatory → Dueling Line and no longer emits any hand notification.

WHY this is the right shape, not just a dodge:
- The hand transit only ever existed because the *old* play path required `Location == Hand` to move the card out of hand onto the line. That path is gone — the card is auto-played by `stSetNextCombatCard`, which calls `moveCard` directly and does not care where the card came from. The staging step has had no purpose since the auto-play restore; it was leftover scaffolding.
- A card the player never gets to decide about should not appear in their hand. Showing it there and then yanking it was always a lie about the game state, and it is exactly the lie that kept desyncing.
- `Technique_01090` already documents this precedent in a code comment: it does direct DB moves specifically because `EventCardAddedToHand` "would fire after the dueling-line move and put the card back into the hand."

WHY `parkCard` (Purgatory) rather than leaving the card on top of the Faction Deck until `stSetNextCombatCard`: between the maneuver action and that state, `DUEL_RESOLVE_MANEUVER_EVENTS` drains the event queue. Anything in there that draws from the Faction Deck would draw the card that was just promised to the duel line. The old `EventCardAddedToHand` removed the deck row immediately and I did not want to give that safety back. Purgatory is the documented idiom for "row moved now, `Location` set by a later step" (see `moveCardInDeck`'s comment), `Theah::buildCity` loads Purgatory into the world, and `stDuskPhaseDiscardEvents` sweeps anything stranded there — so a crashed flow degrades to a discard rather than a lost card.

Added `addCardToWorld` in both places. In the maneuver because `getCardObjectFromDb` returns a detached instance, and in `stSetNextCombatCard` because if the world was rebuilt in between, `getCardById` falls back to a fresh DB copy that nothing writes back (this is the 2026-04-14-04 RiskClone failure mode).

Client side needs no change: `notif_updateRoundWithCombatStats` still renders the duel row from `args.combatCard`, and its hand-removal branch now correctly finds no hand element and skips. Normal combat-card plays are completely untouched.

If Broken Time *still* misbehaves after this, the bug is not in the hand at all and I have been looking at the wrong layer for three sessions.

### Ruled out while chasing this

- **`controllerId` being 0 on the card.** Was my leading theory: `moveCard` never sets `ControllerId`, and `EventCardAddedToHand` goes through `moveCard` while the normal draw path (`playerDrawCard`) sets `ControllerId` explicitly — so a card entering hand via the event could plausibly carry a stale controller and fail the `player_id == controllerId` guard. Disproved: `buildDecks` creates every faction-deck card with `createCardInLocation($id, $location, $playerId, $playerId)`, so faction-deck cards already have the right controller, and the discard/reshuffle handler preserves it. Worth recording so nobody else spends time here.
- **`getCardElement` id type mismatch.** The manager's `getId` is `factionhand-card-${card.id}`, and `contains`/`removeCard` both compare through `getId`, so number-vs-string cannot matter.

I still do not know exactly which condition in `notif_updateRoundWithCombatStats` fails for this card, and that bothers me. The explicit notification makes the outcome correct regardless, but if this card ever misbehaves again, get a console capture of the notification args rather than reading the handler.

## Follow-up: pay-for-maneuver cost chip rendered at the top-left of the *screen*

Eddie: in `duelPayForManeuverFromCombatCard` the wealth cost chip appeared in the upper left corner of the page instead of on the combat card in the duel table.

Root cause was pure CSS, not the JS. `jstpl_hand_wealth_cost_chip` carries `._7sfs-hand-wealth-cost`, which is `position: absolute; top: 0; left: -15px`. Every other place that template is used targets a hand card or a `chooseList` item — both are `._7sfs-card`, which is `position: relative`, so the chip anchored to the card. `._7sfs-duel-row-combat-card` (the duel table's 72x98 card div) had no `position`, so the chip resolved against the nearest positioned ancestor and flew to the page corner.

Fix: `position: relative` on `._7sfs-duel-row-combat-card`. With the base class's `margin-left: 15px` cancelling the chip's `left: -15px`, the chip lands exactly on the card's top-left corner — no new chip class or JS-side offsets needed.

WHY not a duel-specific chip class or inline styles: the chip template hardcodes its classes, so a variant would mean either a second template or JS class juggling, and the existing offsets already produce the desired position once a containing block exists. This was a missing containing block, so fix the containing block.

Checked before adding `position: relative`: nothing else absolutely positioned lives inside a duel-row combat card. `._7sfs-number-order-chip` is the only other absolute chip that could collide, and it is only placed into `chooseList` items by `addSortTagToCard`. Tippy tooltips are body-appended. No z-index stacking change since `relative` without `z-index` doesn't create a stacking context.

The 30px chip on a 72px-wide card is proportionally chunkier than on a 110px hand card. Left it alone — Eddie asked for placement, not sizing.

Supporting evidence that the containing-block theory is right: `._7sfs-engaged` (added to *gambled* combat cards) sets `transform: rotate(90deg)`, and a transform creates a containing block for absolutely positioned descendants. So on a gambled card the chip would already have anchored to the card (rotated with it), while a non-gambled card had no positioned/transformed ancestor at all and the chip fell through to the initial containing block — the document's top-left. If this bug is ever reported as "sometimes it's on the card", that asymmetry is why.

Also confirmed this is the **first** place the chip template is used on anything that isn't `._7sfs-card` (hand card or `chooseList` item, both already `position: relative`). The latent bug had no way to show up before this state existed.

### Still reported broken — two worlds, and they need opposite fixes

Eddie says the chip is still at screen-left after the CSS change. The chip is only created inside `if (div)` where `div = $('duel_round_{round}_combat_card_{cardId}')`, and the handler would have thrown at the `args.args._private.cost` read before that if `_private` were missing (no chip at all). So exactly one of:

1. **The on-card chip exists and the CSS isn't live.** There is no build step; the CSS edit is local until SFTP-uploaded to Studio. Then this is a deploy/cache issue, not a code issue.
2. **`$(cardDivId)` returned null, no on-card chip was ever created, and the chip Eddie sees is the status bar chip** from `jstpl_status_bar_wealth_cost_chip` — which is intentional and present in ~8 other pay states. Then the real bug is the div id (round number or card id mismatch) and the CSS change is harmless but irrelevant.

Distinguishing observation is trivial: is the chip *inside the status bar sentence* ("You must pay 🪙 Wealth for the Maneuver…") or *floating loose* at the page corner? Asked Eddie rather than guessing.

WHY not just patch both: adding inline positioning from JS as a belt-and-braces would hide which world we are in, and world 2 would still be broken (no chip on the card at all). The 2026-08-08 combat-card-in-hand saga in this same file is what happens when you keep adding conditions instead of getting one observation.

## Follow-up: remove Decline from duelUseManeuverFromCombatCard

Eddie: Decline button (and its handler) should go.

WHY remove it under the hub model: entering this state is already an affirmative "I want a Maneuver" from `duelChooseAction`. Skipping is "don't click Maneuver" at the hub, or Back (when shown) which returns to the hub *without* clearing `DUEL_PENDING_MANEUVER_CARD`. Decline was the only path that both left the state and wiped the pending flag — that "skip forever" affordance is wrong once Maneuver is a hub option you opt into.

Removed:
- JS `btnDecline` → `actDuelUseManeuverFromCombatCardDeclined`
- PHP `actDuelUseManeuverFromCombatCardDeclined` (deleted the pending global + `maneuverDeclined` transition)
- `possibleactions` entry + `maneuverDeclined` transition in `states.inc.php`

Back stays. Gambled / abnormalFlow still hide Back (pre-existing); those entries still commit the player to picking a Maneuver button once they opened the chooser.

## Unfinished / verify in Studio

- Plain combat card → Technique then Maneuver (and reverse)
- Back from Maneuver (pending still available at hub); no Decline button
- Gambled combat card with Maneuver
- Broken Time: second card auto-lands on the line with no hub stop in between; its Maneuver is then offered at the hub; card does not survive the duel
- Broken Time: card never appears in hand at any point; it goes chooser → duel row. Hand count in the score panel must not change.
- Broken Time: refresh mid-duel still shows the card on the duel line (Purgatory park is transient, but confirm nothing strands it there)
- Broken Time: `combatCardAvailable` stays false after the auto-play (count is 2)
- Roll the Bones still adds values without arming a pending Maneuver on the sunk card
- Cost chip sits on the combat card's top-left corner in the duel table, and is gone on leaving the state (the `dojo.destroy` in `OnLeavingState` was already there)
