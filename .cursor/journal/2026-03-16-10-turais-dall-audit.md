# Turais Dall (_02012) Audit

## Context

Continuing the card audit series. Audited _02012 (Turais Dall) — an Eisen Berserker character with a Reaction that triggers on challenge issuance and a Technique that self-wounds for threat removal.

## Bug Found & Fixed: Reaction missing challengerId check

The card text says "When Turais issues a challenge" but the `handleEvent` in `Reaction_02012` only checked for `EventChallengeIssued` without verifying that Turais (`$owner->Id`) was the `$event->challengerId`. This meant the reaction would fire when ANY character issued a challenge, not just Turais.

WHY this matters: Without this check, an opponent issuing a challenge against Turais (or even a completely unrelated challenge elsewhere on the board) would trigger Turais's self-wound + en garde reaction. That's both wrong and potentially exploitable — you could bait a Turais player into wounding themselves by issuing unrelated challenges.

The fix was simple: added `$event->challengerId == $owner->Id` to the condition. Pattern matches `Reaction_01065` (Henri) which does the same check for "when Henri issues a challenge."

## Technique: Clean

The Technique's "remove all threat" is implemented by reading the current duel threat and adding it to parry. In the duel calculation system, parry offsets threat, so this effectively zeroes out the threat. This is the standard approach — I checked `EventDuelCalculateTechniqueValues` usage and this matches how other techniques contribute values.

The self-wound is gated by `$owner->Wounds + 1 < $owner->ModifiedResolve`, which is the standard "can survive the wound" check. Same check is used in the Reaction.

## Observations

Both the Reaction and Technique have the same preconditions: Berserker trait + wound survival. This is consistent with the "Berserker" keyword mechanic — these abilities are explicitly gated behind the trait, which means if the trait is somehow removed (e.g., by another card effect), both abilities become unavailable. That seems intentionally defensive.
