# Devil Jonah's Bones (_03cd05) Implementation

Third faf expansion card. CityAttachment with a Forced wound on equip and a multipart effect when the equipped character gambles during a duel: +1 reveal, optional reveal-from-bottom mode, and inverted sink direction.

User asked for `03cd03` but `03cd03` (Chance Meeting) is already implemented. The task description (gambling, top/bottom choice, DUEL_GAMBLE_REVEALED) only matches `03cd05` Devil Jonah's Bones, the new untracked file. Treated it as a typo.

## Card Text
> **Forced:** When a character equips this card • Wound them.
>
> When the equipped character gambles during a duel, reveal an additional card. You may reveal cards from the bottom of the deck instead of the top. If you do, cards sink to the top of the deck instead of the bottom.

## Architecture: New DUEL_GAMBLE_SETUP State

**WHY a new setup state instead of stuffing logic into stDuelGambleRevealed:** The card needs to react to "the equipped character gambles" *before* cards are physically peeked from the deck. If we modified count + bottom-flag inside `stDuelGambleRevealed` itself, the player choice to reveal-from-bottom would have to happen *during* a game-type state, which the framework doesn't support (game states can't be interactive). Splitting setup from reveal lets reactions and player choices fully resolve before any deck reveal happens.

State graph (added between every existing entry to DUEL_GAMBLE_REVEALED and the reveal itself):

```
* → DUEL_GAMBLE_SETUP (game, stDuelGambleSetup: queues EventGambleSetup)
  → DUEL_GAMBLE_SETUP_EVENTS (game, stRunEvents)
       ↳ "03cd05" → DUEL_GAMBLE_SETUP_03CD05 (activeplayer: top/bottom choice)
       ↳ "reaction" → DUEL_GAMBLE_SETUP_REACTIONS
       ↳ "pay" → DUEL_GAMBLE_SETUP_PAY_FOR_REACTION
       ↳ "endOfEvents" → DUEL_GAMBLE_REVEALED  ← original entry point
```

All four prior transitions to `DUEL_GAMBLE_REVEALED` were rerouted to `DUEL_GAMBLE_SETUP`:
- `DUEL_CHOOSE_ACTION.chooseGambleCard`
- `DUEL_COMBAT_CARD_EVENTS["01135"]`
- `DUEL_SET_NEXT_COMBAT_CARD.rollTheBones`
- `DUEL_CHOOSE_GAMBLE_CARD_EVENTS["01135"]`

## Event: EventGambleSetup

Carries `actorId` (duel round actor) and `playerId` (their controller). Followed exactly the EventDuelAttemptGamble shape — minimal fields, no-op handler in EventHub, registered in `Events::GambleSetup`.

The 03cd05 reaction listens for this event and queues a `TransitionEvent("03cd05", sourceId=this->Id)` so the framework routes from `DUEL_GAMBLE_SETUP_EVENTS` to the new player-choice state.

The +1 reveal does NOT happen via this event — instead 03cd05 overrides `getNumberOfGambleCardsToReveal(Theah, Character, &explanations)` per the established Sarafina (`_01010`) / Ivy (`_02042`) / Roll the Bones (`_01114`) pattern. That method is invoked by `Theah::getNumberOfGambleCardsToReveal`, which iterates every card in play and sums their contributions, then `actDuelActionGamble` (or `Maneuver_01114`'s setup) writes the total into `GAMBLE_REVEAL_COUNT` *before* the setup state ever runs. This is the right place because the count is a steady-state property of the play area, not something to mutate transiently. Initial draft did it via `handleEvent(EventGambleSetup)` mutation; refactored to the override pattern after review.

The transition's `playerId` is set to the equipped *character's* controller, not the event's `playerId`. WHY: in normal play these match (the card text scopes the ability to "the equipped character gambles"), but defensively reading from `attachedTo()` keeps it correct if the equipped character changes controller via some future mechanic.

## Top/Bottom Choice State

`State_duelGambleSetup_03cd05.php` placed in `modules/php/States/faf/` per the Penya/Chance Meeting pattern. Active-player state with `actFromCardWithId` dispatch. `id == 1` = top (default, clears the global), `id == 2` = bottom (sets `GAMBLE_REVEAL_FROM_BOTTOM = true` and notifies all).

The default for `id == 1` explicitly *resets* the global to false rather than just leaving it. WHY: if a player gambled with bottom-mode last round and the global never got cleared, a subsequent gamble where they pick "top" should still work as top. The `stDuelEndOfRound` cleanup handles round boundaries but defensive reset here is cheap.

JS button wiring in `OnUpdateActionButtons.faf.js` — two buttons "Reveal from Top" / "Reveal from Bottom".

## Reveal-From-Bottom Plumbing

New global: `Game::GAMBLE_REVEAL_FROM_BOTTOM`.

Three call sites consume it:

1. **stDuelGambleRevealed** — branches between `getCardsOnTopOfPlayerFactionDeck` and a new `getCardsOnBottomOfPlayerFactionDeck`.
2. **argsDuelChooseGambleCard** — same branch so the UI shows the correct cards to choose from.
3. **actGambleCardChosen** — when sinking unchosen cards, passes `$fromBottom` as the `bOnTop` param to `insertCardOnExtremePosition`. WHY this is correct: `bOnTop=true` means "place on top." When normally sinking after a top-reveal, we pass `false` (sink to bottom). When revealing from the bottom, the card text says "cards sink to the top of the deck instead of the bottom" — so we pass `true`. Variable name `$fromBottom` happens to align with the `bOnTop` semantic by coincidence, which is a bit confusing — added a comment.

Cleared in `stDuelEndOfRound` alongside the other gamble globals.

## getCardsOnBottomOfPlayerFactionDeck

New helper in DeckTrait. The BGA Deck library only exposes `getCardsOnTop`/`getCardOnTop` — no bottom variant. Implemented by:
1. Reusing `getCardsOnTopOfPlayerFactionDeck` for the deck-too-small reshuffle path (avoids duplicating that logic).
2. Calling `getCardsInLocation($location, null, "card_location_arg")` which sorts ASC, taking the first `$nbr` entries.

Per the BGA Deck doc, `card_location_arg` lower = bottom, higher = top. Confirmed in `_ide_helper.php` line 2498.

## Forced Wound on Equip

Implemented inline on the attachment via `handleEvent(EventAttachmentEquipped)` rather than a separate `Reaction_03cd05.php`. WHY: it's a Forced ability (mandatory, no player choice), and the precedent set by `_01075` (Tabard of the Fallen Musketeer) and `_03cd01` (Penya) is to handle Forced abilities directly in the card class's `handleEvent`. CardReaction would require `setUsed`/`isAvailable` plumbing that doesn't fit a Forced effect.

The wound source is `$this->Id` (the attachment), which is consistent with how `Maneuver_01135` wounds — the source of the wound is the card text triggering it.

## Pre-commit Hook

The hook checks for `createAttachmentEquippedEvent()` callers — we *react* to that event but don't create it, so the rule doesn't apply. Nothing else triggered.

## Files Created/Modified

- `modules/php/cards/faf/_03cd05.php` — implemented (was a stub)
- `modules/php/theah/events/EventGambleSetup.php` — new
- `modules/php/States/faf/State_duelGambleSetup_03cd05.php` — new
- `modules/php/States.php` — added 5 new state constants
- `modules/php/Game.php` — added `GAMBLE_REVEAL_FROM_BOTTOM` global
- `modules/php/theah/Events.php` — added `GambleSetup` constant
- `modules/php/theah/EventHub.php` — added no-op handler + use
- `modules/php/EventFactory.php` — added `createGambleSetupEvent`
- `modules/php/StatesTrait.php` — added `stDuelGambleSetup`, branched `stDuelGambleRevealed` on from-bottom, added cleanup
- `modules/php/DeckTrait.php` — added `getCardsOnBottomOfPlayerFactionDeck`
- `modules/php/FrameworkActionsTrait.php` — `actGambleCardChosen` honors from-bottom for both reveal and sink
- `modules/php/ArgumentsTrait.php` — `argsDuelChooseGambleCard` honors from-bottom
- `states.inc.php` — added 4 setup states; rerouted 4 transitions
- `modules/js/OnUpdateActionButtons.faf.js` — top/bottom buttons

## Open Questions / Followups

- **Multiple equipped Devil Jonah's Bones** — text says "Unique" so realistically only one in play, but if two existed, `EventGambleSetup` would fire both `handleEvent` calls and queue two transitions, double-incrementing reveal count. Acceptable given the Unique trait.
- **Roll the Bones (01114) interaction** — `Maneuver_01114` sets `GAMBLE_REVEAL_COUNT` directly in its setup. Since 01114's path also routes through `DUEL_SET_NEXT_COMBAT_CARD.rollTheBones → DUEL_GAMBLE_SETUP`, the EventGambleSetup fires after 01114's count is already set, so 03cd05's `+1` stacks correctly on top. Untested but the order should work.
- **From-bottom reveal with Sorcerer (Reaction_02042)** — Ivy's reaction takes a Sorcery from the revealed cards into hand. If revealed-from-bottom, the bottom card moves to hand. The decrement of `GAMBLE_REVEAL_COUNT` still works. Not specifically tested.
- **Zombie in DUEL_GAMBLE_SETUP_03CD05** — falls through to `nextState()` with no choice, which lands in events without setting `GAMBLE_REVEAL_FROM_BOTTOM`. So a zombied player defaults to top, which is the safe default.
