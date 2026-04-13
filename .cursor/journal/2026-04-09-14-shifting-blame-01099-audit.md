# Shifting Blame (01099) Audit

## Bug Found & Fixed

`Reaction_01099a::handleEvent()` had a `sourceId == 0` branch that triggered the reaction for framework-caused discards. Card says "due to your effect" — framework discards are nobody's effect. This was a direct copy-paste expansion gone wrong: someone added a `sourceId == 0` branch (maybe thinking "cover the edge case") but it bypasses the ownership check entirely.

Compare with Sanjay's reaction (01097) which has nearly identical text ("After an opponent discards a card due to your effect • Draw a card") and handles this correctly — it goes straight to `$source?->ControllerId == $owner->ControllerId` which naturally fails when source is null.

The fix was simple: remove the `sourceId == 0` block, leaving only the `sourceId != 0` path that properly checks source card ownership.

## Observation: Event Type Coverage

01099a listens to three discard event types (`EventCardDiscardedFromPlay`, `EventCardDiscardedFromHand`, `EventCardAddedToCityDiscardPile`) while 01097's nearly identical reaction only listens to `EventCardDiscardedFromHand`. Both cards say "discards a card" generically. 01099a's broader coverage is arguably more correct — not flagging this as a bug but worth noting for future 01097 review.

## Everything Else Clean

- Scheme effect (add renown to Docks) is straightforward and correct.
- Reaction B (renown after opponent claims location) is clean: proper opponent check, proper "different location" filtering via button exclusion, correct renown creation.
