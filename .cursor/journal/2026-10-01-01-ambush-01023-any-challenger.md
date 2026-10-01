# Ambush Reaction_01023 — trigger on any challenge

## Context
Prior session: First Player reaction-order choose (Ambush pay divert was part of that). User asked Ambush to fire no matter who initiates the challenge.

## Bug
`handleEvent(EventChallengeIssued)` required `$risk->ControllerId == $event->playerId`, so only the issuer's Ambush could be offered.

## WHY the fix
Card text: "When a challenge is issued • Other characters cannot intervene." No "you issue" restriction. Passive Brute discount is location-based only; reaction timing is any challenge issued while Ambush is in hand.

Same shape as Reaction_02030b (hand Risk on any EventChallengeIssued without issuer match).

## Change
Dropped the issuer ControllerId gate. Still requires `isAvailable()` + `LOCATION_HAND`. Transition still targets Ambush's controller (they choose Prevent/Pass).
