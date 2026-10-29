# Sanjay challenge cancelled after prior Vittoria redirect

## Report
Sanjay (03037) Influence City Action vs engaged Vittoria at Forums. Vittoria had already used her redirect earlier the same Day. Angelo/Burantino engarde there, no city cards. Chat: "Challenge was cancelled." User wondered if Vittoria's effect was persisting.

## Diagnosis
Not the reaction still firing (Used resets only at Dusk — same-Day Used stays true). Sticky `CHALLENGE_CANCELLED`.

`Reaction_01014::cancelEvents()` always set that global whenever a Thug redirect failed `isValidTargetForAbility` (or `loadAbility` was null). Those call sites are the *non*-ChallengeIssued branches — ChallengeIssued redirects `releaseEvent` and never cancelEvents. So every failed wound/engage/move/heal/CharacterTargeted redirect armed the duel-cancel flag even when no challenge was in flight.

Sanjay is a hub-exit challenge: target → EVENTS → `03037_2` → `stTechniqueAvailable`. That gate reads `CHALLENGE_CANCELLED` *before* `stIssueChallenge` resets it. Stale true → `skipChallengeBeforeTechnique` → generic "Challenge was cancelled." No Accept UI, no ChallengeIssued log. Matches the report.

March Vittoria audit said unconditional set was "safe because challenge flow resets it" — wrong after UL journal 2026-10-19 proved the technique gate sees the flag first. UL (01032), Cross (01053), Blood Like Winter (02048) already only arm when cancelling an in-flight challenge. Vittoria was left behind. Altruistic (03031) had the same unconditional set with zero challenge hold — same poison.

Engaged Vittoria / engarde Thugs / Forums are red herrings for this path.

## Fix
- `Reaction_01014::cancelEvents($game, $state)`: arm flag only if held `challengeIssuedEvent`, or held `characterTargetedEvent` during state 4511 with abilityId == CHOSEN_ACTION (UL shape).
- `Reaction_03031::cancelEvents`: drop the flag set entirely (never holds ChallengeIssued).
- Tests: Vittoria wound-fail does not arm; 4511 CharacterTargeted fail does; Altruistic reject-performer asserts flag false.

## Opinion
User's "persisting" intuition was exactly right — just the global, not the reaction. Easy to miss because cancelEvents is mostly hit on failed redirects, which feel like "I used Vittoria and something fizzled," then the *next* challenge dies with no Vittoria prompt.
