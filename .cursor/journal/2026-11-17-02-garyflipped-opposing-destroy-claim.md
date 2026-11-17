# Reaction_05GaryFlipped → opposing destroy claim

## Context
`_05GaryFlipped` Text reaction line is now: **When an opposing character is destroyed • Claim this location.** (was duel-adversary / Rena's adversary wording that the old impl encoded).

## What changed
`Reaction_05GaryFlipped::handleEvent`:
- Dropped `IN_DUEL` + challenger/defender seat matching.
- Gate like `Reaction_04004`: destroyed enemy (`ControllerId` ≠ Rena's), same `Location` as Rena, plus existing claimability / controlled / not-discard-or-locker checks.
- Claim payoff unchanged (stash location, Claim/Pass buttons, `createLocationClaimedEvent`, illegal-claim UserException before parent).

## WHY opposing ≠ duel adversary
Printed text literally says opposing character, not adversary. Pattern D + `_04004`: opposing = different controller AND same location; destroy Location still readable via `runEventHubAfterCards = true`. Keeping duel-only would miss out-of-duel kills at her site.

## Tests
Rewrote `Reaction_05GaryFlipped_Test` for ally / elsewhere / home / unclaimable negatives + claim/pass/illegal-claim. Passes.

## Not touched
Combat-card attachment passive, Technique, A-side Forced (draw-two-then-flip from earlier today).
