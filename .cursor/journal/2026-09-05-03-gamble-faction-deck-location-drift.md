# Gamble confirm: "Card is not in your faction deck"

## Report

Eddie: Trial of Faith shown among 2 gambled combat cards; selecting it and Confirm threw "Card is not in faction deck" and UI wouldn't accept it.

## Mechanism

`argsDuelChooseGambleCard` builds the picker from BGA deck tops (`getCardsOnTopOfPlayerFactionDeck` → `card_location`).

`actGambleCardChosen` then validates the *serialized* property:

```php
$card = $this->getCardObjectFromDb($id);
if ($card->Location != $deckName) {
    throw new UserException(... "Card is not in your faction deck.");
}
```

Same dual-store class as Aug 8 discard drift (`2026-08-08-01-discard-location-drift.md`), but the inverse direction: `card_location = Faction-<pid>` while `Card->Location` is still something else (almost certainly `Hand`). UI offers the card; confirm rejects it. Stuck — no cancel out of choose-gamble that unsticks Location.

`buildCity()` only self-heals discard piles (`repairDiscardPileLocations`). Faction deck is never loaded/repaired there (and shouldn't blanket-reconcile — Purgatory counterexample). So this drift survives forever until a correct sink/move path overwrites Location.

## Likely producers (Hand → Faction without Location write)

`Reaction_03006::sinkOneFromHand` (Premonition) and `Reaction_03007::sinkOneFromHand` (Matushka's Shears) both:

```php
$deck->insertCardOnExtremePosition($cardId, $deckName, false);
```

No `$card->Location = $deckName`, no `updateCardObjectInDb`, no `createCardAddedToFactionDeckEvent`. Deck row moves; serialized Location stays `Hand`.

Correct pattern elsewhere: `EventFactory::createCardAddedToFactionDeckEvent` → EventHub sets Location then insertCard.

WHY these two used raw insert: copied stage-machine sink UX without going through the event that owns Location sync. Journal for 03006/03007 never mentioned Location.

## How to confirm on a stuck table

Compare for the Trial of Faith card id: `card.card_location` vs unserialize Location in `card_serialized`. Expect Faction vs Hand (or Discard if another path).

## Fix (done)

**Sources:** `Reaction_03006` / `Reaction_03007` `sinkOneFromHand` now set `$card->Location = $deckName` and `updateCardObjectInDb` after `insertCardOnExtremePosition`. Immediate write (not queued `EventCardAddedToFactionDeck`) because the next pick step re-queries hand by `card_location` in the same request — must leave Hand before `advanceToNextPick`.

**Gamble confirm:** `actGambleCardChosen` validates `$deckCard['location']` (BGA deck row) instead of serialized Location. If Location drifted, self-heal before continuing so later combat-card path sees a coherent object. Top/bottom-N membership check unchanged.

Stuck live tables: next Confirm after deploy should succeed and heal the chosen card. Unchosen drifted cards in the deck remain stale until something else touches them — only the chosen card is healed here. Full faction-deck repair in buildCity deliberately not added (Purgatory counterexample from Aug 8).

Trial of Faith unchanged — innocent victim.
