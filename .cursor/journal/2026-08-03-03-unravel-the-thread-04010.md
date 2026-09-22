# Unravel the Thread (_04010)

## Card

- WealthCost 2 (Roman II on art) — **NOTE:** `_04010.php` currently still has `WealthCost = 0`; not fixed in reaction-timing pass
- Riposte 2 / dashed Parry / dashed Thrust
- Traits: Sorcery, Sorte
- **Sorcerer Reaction:** When your performer reveals this card while gambling • Reveal additional cards equal to their Influence. Their Sorceries gain +1 Parry this round.
- **Sorcerer Action:** Sink up to two cards from a single discard pile. Then, draw a card and sink this card.

## Classification

| Clause | Pattern | Notes |
|---|---|---|
| Sorcerer Reaction on gamble reveal of **this** card | Not RiskReaction | Deck reveal, no hand pay. `CardReaction` + `ISorcererAbility`. |
| Sorcerer Action sink/draw/sink-self | Pattern B | `RiskAction` + Sorcerer performer + discard-pile states |

## WHY: CardReaction not RiskReaction

RiskReaction assumes hand pay (`Location == HAND` pre-commit, "Faction Hand >" description). Printed trigger is "reveals **this card** while gambling" — peeked from the faction deck.

## WHY: EventHub addCardToWorld on gamble reveal

`buildCity()` does not load faction-deck cards. Without hub `addCardToWorld` before the card pass (`runEventHubAfterCards=false`), a Reaction on the gambled card never sees `EventDuelGambleCardsRevealed`.

## WHY: Parry sticky via Game global

After resolve, Unravel often sinks back to the deck and leaves `$theah->cards`. Sticky on the Reaction would miss calc. `UNRAVEL_THE_THREAD_CONTROLLER_ID` + EventHub apply on Sorcery combat cards; clear in `stDuelEndOfRound`.

## WHY: Re-fire reveal for new ids only

Bump `GAMBLE_REVEAL_COUNT` for the choose UI. Re-queue `EventDuelGambleCardsRevealed` with only newly peeked ids so Ivy can react without re-offering Unravel.

## Action shape

Sorcerer performer → **always** `04010` pile chooser (every player discard + City Discard, even empty) → `04010_2` up to 2 cards (Pass = 0, exclude self) → sink (player→owner faction bottom; **City→City Deck**) → draw → sink self → ActionResolved.

### Follow-up (Eddie 2026-08-03)

Eddie asked to insert a pile-chooser state including City Discard. The state already existed but auto-skipped when 0/1 non-empty piles and hid empty piles (City disappeared when empty). Fixed: always transition `"04010"`; always list all player discards + City Discard.

## WHY: Reaction AFTER chooseList visible (Eddie follow-up)

Original path used `createReactionTransitionEvent` → `DUEL_GAMBLE_REVEALED_REACTIONS` / `playerReaction` **before** `duelChooseGambleCard`. Player got Use/Pass without seeing the revealed cards in chooseList.

**New shape (mirrors Proper Drama 03047 timing):**
1. On `EventDuelGambleCardsRevealed`, queue `createTransitionEvent(..., "04010", reactionId)` — priority 8, **after** Ivy-style reaction transitions (priority 6).
2. New state `duelGambleRevealed_04010` (`DUEL_GAMBLE_REVEALED_04010 = 527304010`) under `DUEL_GAMBLE_REVEALED_EVENTS` transition `"04010"`.
3. Args: public `cards` from current `GAMBLE_REVEAL_COUNT` peek (same deck edge as choose).
4. JS: show chooseList (selectionMode 0) + Use / Pass.
5. Both Use and Pass → back to `DUEL_GAMBLE_REVEALED_EVENTS` (not straight to choose). WHY: leftover transitions like Proper Drama `03047` must still run; Use also needs the events path for additional-card reveal + Ivy.

**Deck-card setUsed pitfall:** Framework loads a fresh `_04010` for the action; `setUsed`→`getCardById` would load a *second* copy and persist `Used=false`. Overrode `actFromCardWithId` / `actFromCardPass` on `_04010` to `addCardToWorld($this)` first (same idea as `_02045`).

## Files

`_04010.php`, `Reaction_04010.php`, `Action_04010.php`, `State_highDramaPhase04010`/_2, `State_duelGambleRevealed_04010`, EventHub, Game const, StatesTrait, states.inc.php, States.php, bas JS trio, EventHandlers.js.
