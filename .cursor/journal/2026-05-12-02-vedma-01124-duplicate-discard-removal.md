# Ved'ma (01124) — Duplicate "removed from discard pile" notification

## The Bug

Playing Ved'ma's Sorcerer action produced two identical-looking chat lines:
"*Sorcery Name* removed from *Player*'s discard pile."

## Why

Two distinct `cardRemovedFromPlayerDiscardPile` events fire per play, for different card ids, but with the same visible name/image:

1. `Action_01124::stateFromAction` (line 162) fires the event for the **original** Sorcery card before hiding it.
2. The clone (`_01124_RiskClone`) is created in hand with the original's `Name` and `Image` copied. When the clone is discarded from hand at end of play, `EventCardDiscardedFromHand`'s EventHub handler briefly routes it into the discard pile, and then `_01124_RiskClone::handleEvent` fires another `cardRemovedFromPlayerDiscardPile` for the clone to clean it up.

Because the clone shares the original's name/image, the two notifications are indistinguishable to the player.

`_01154_RiskClone` does the same thing but it's masked there — Corpse Speak sends the original back to the discard pile afterward, so the closing "added to discard pile" notification visually balances things. Ved'ma sends the original to the locker, so the second "removed" notification stands alone and looks like a duplicate.

## Fix

Passed `$messageHidden = true` to the second `createCardRemovedFromPlayerDiscardPileEvent` in `_01124_RiskClone.php:32`. JS state still updates (clone gets filtered out of the player's discard array), but the chat message becomes the generic "[Hidden Card] removed from..." which disambiguates it from the real notification for the original card.

## WHY this approach over alternatives

Considered intercepting the clone's discard so it never enters the discard pile at all — would have produced the cleanest UX but required changing event flow / routing the clone through a non-standard discard path. The `messageHidden` flag exists for exactly this kind of bookkeeping cleanup, so it's the minimal-risk fix. The JS notification still needs to fire to keep client state in sync (`notif_cardRemovedFromPlayerDiscardPile` filters by card.id).

## Not touching 01154

`_01154_RiskClone` has the same structural duplication but the visible result is acceptable because the original returns to the discard pile. Leaving it alone.
