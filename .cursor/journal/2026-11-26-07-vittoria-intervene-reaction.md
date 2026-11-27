# Vittoria — intervene onto her did not open Reaction

## Report
Redirecting a challenge to Vittoria (Intervene) did not trigger Reaction_01014.

## Root causes (two)

### 1. Recruit Brutes blocked after Accept/Intervene
`canEnterPlayFromHand` only treated `IN_DUEL` as the duel exception. Intervene sets
`CHALLENGE_ACCEPTED` then drains `EventCharacterIntervened` in GENERATE_THREAT —
`IN_DUEL` is still false until duel start. Bruno-only hand → `thugsInHand` false →
no offer (same as Angelo-less / no in-play Thug).

WHY fix: Accept/Intervene is already past refuse; printed "except during a duel"
covers that window. ChallengeIssued / CharacterTargeted before Accept keep
`CHALLENGE_ACCEPTED` false — pre-accept Vittoria challenge window still blocks
Recruit Brutes (existing Bruno test).

### 2. Stale savedAbilityId dropped Intervened on redirect
ChallengeIssued / Intervened never call `shouldReactToEvent`. A leftover
`savedAbilityId` from an earlier wound made inPlayThug prefer `loadAbility` →
`isValidTargetForAbility` → `cancelEvents` and drop the held Intervened (challenge
stayed on Vittoria). Fix: treat `characterIntervenedEvent` like ChallengeIssued /
CardMoving (skip re-validate), and `clearSavedAbility` when opening those paths /
dusk / cancelEvents.

## Not doing
Not firing CharacterTargeted from actIntervene — technique overwrite of abilityId
would miss shouldReactToEvent; Intervened is the right signal (already present
since 2026-06).

## Tests
Reaction_01014: intervene redirect with stale ability; intervene + Bruno when
CHALLENGE_ACCEPTED. 23/23 green.
