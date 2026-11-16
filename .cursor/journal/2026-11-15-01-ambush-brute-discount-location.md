# Ambush Reaction_01023 Brute discount missing with Buratino

## Report
Player had Buratino (Brute) at the challenge location, played Ambush (`Reaction_01023`), and did not get the -1 cost.

## Root cause
`getReactionFromHandDiscount` reads `CHOSEN_LOCATION`. That global was only stamped in `stSetupChallenge`.

Challenge issue order when a technique is activated:
1. `stIssueChallenge` queues `EventChallengeIssued` (no site yet)
2. `ACTIVATE_TECHNIQUE_EVENTS` processes ChallengeIssued → Ambush offered → pay → discount calc
3. Only later `stSetupChallenge` sets `CHOSEN_LOCATION`

So Ambush pay ran with a null challenge site → Brute filter never saw Buratino.

Pass / no-technique paths went Issue → SETUP (sets site) → SETUP_EVENTS, so discount worked there. Technique-activated path was the hole.

## Fix
1. **`stIssueChallenge`**: set `CHOSEN_LOCATION` from performer location when the challenge is issued (same moment Ambush can pay).
2. **`Reaction_01023`**: if `CHOSEN_LOCATION` empty, fall back to `CHOSEN_PERFORMER` / `CHOSEN_TARGET` location (same shape as `interventionCheck`). Also add discount explanation string (was silent).

## WHY not only the reaction fallback
Setting the site at issue time is the correct lifecycle for every ChallengeIssued consumer, not just Ambush. SETUP still re-sets it (harmless). Fallback is belt-and-suspenders for Move Along / custom issue paths that might skip SETUP.

## Tests
- `Reaction_01023`: existing Brute discount + new fallback when `CHOSEN_LOCATION` unset but performer at Brute site
- `StatesTrait_Challenge`: `stIssueChallenge` records `CHOSEN_LOCATION`

57 green across those two suites.
