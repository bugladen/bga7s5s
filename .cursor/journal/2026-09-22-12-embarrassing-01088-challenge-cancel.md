# You're Embarrassing Yourself (01088) — challenge cancel doesn't stop duel

## Bug
Angeline (_01196 Mercenary) challenged Bastien (_01063 — user said "Odette 01063", meaning Bastien under Odette's player). Opponent played You're Embarrassing Yourself (_01088). Duel proceeded anyway.

## Root cause
`stChallengeActionCheckCancelled` checked `CHALLENGE_ACCEPTED` **before** `CHALLENGE_CANCELLED`. Reaction_01088 correctly sets `CHALLENGE_CANCELLED=true` after pay on `EventRiskReactionTriggered`, but that was ignored whenever accepted was already true.

## Why accepted could be true at CHECK_CANCELLED
1. `stIssueChallenge` only reset `CHALLENGE_CANCELLED`, not `CHALLENGE_ACCEPTED`.
2. Accepted→fizzled (dead defender) went NEXT_PLAYER without deleting `CHALLENGE_ACCEPTED` (only `stDuelEnd` deleted it) — leftover poisoned the *next* challenge.
3. Same-challenge SETUP intervenes (Mourad `Reaction_02003`, Heroic Intervention `Reaction_02058`) set `CHALLENGE_ACCEPTED=true` during SETUP events before CHECK_CANCELLED.

## Why 01088 is especially exposed
Unlike Stubborn / Unyielding, 01088 does **not** set `$event->canceled` on `EventChallengeIssued` (`runEventHubAfterCards=true` — cancel-in-place would skip issuance hub). Flag-after-pay is the right shape for "when issued → cancel it", so 01088 fully depends on CHECK_CANCELLED respecting the flag.

## Fix applied
1. Prefer cancelled over accepted in `stChallengeActionCheckCancelled`; null-safe challenger/defender cleanup; delete `CHALLENGE_ACCEPTED` on cancel.
2. Reset `CHALLENGE_ACCEPTED=false` in `stIssueChallenge` alongside CANCELLED.
3. Delete `CHALLENGE_ACCEPTED` on accept-path fizzle.
4. `Reaction_01088` hardening: gate `!$event->canceled`; use `$event->challengerId` not `CHOSEN_PERFORMER`; stack pay events (Stubborn order); `setUsed` on successful cancel.

## WHY cancel beats SETUP intervene
Printed cancel ("Cancel it") ends the challenge. An intervene that auto-accepted during SETUP should not resurrect a cancelled challenge. Leftover accepted from a prior fizzle is the more common poison for "YEY played, duel anyway" without Mourad/HI in the table.
