# Soline _01089 — -1 Fin must start at Accept/Intervene

## Clarification (user)
Adversaries begin once a challenge is **accepted or intervened**. Soline's -1 Fin
passive starts then. On a Finesse Challenge, threat the adversary sends after
accepting uses the penalized Finesse. Refuse → never adversaries → no penalty.

## What was wrong
Aura applied only on `EventDuelStarted`. Challenge flow is:

1. Accept / Intervene (sets CHALLENGE_ACCEPTED; queues ChallengeAccepted or Intervened)
2. `GENERATE_THREAT` queues `EventGenerateChallengeThreat` (reads ModifiedFinesse)
3. Resolution → duel → `EventDuelStarted`

So Finesse-challenge threat always saw **unpenalized** FIN. Duel itself was fine
once DuelStarted fired.

April audit (`2026-04-09-08`) said duel-scoped apply was correct for the
"adversary = duel opponent" ruling. This clarification moves the start of that
window earlier — Accept/Intervene, still not continuous location aura, still not
on Refuse.

## Fix
`_01089.php`:
- Apply on `EventChallengeAccepted` + `EventCharacterIntervened` (intervene never
  fires ChallengeAccepted — Benci/Heroic Intervention etc.)
- `stackEvent` the FinesseModified from those paths so it drains **before** the
  already-queued GenerateThreat in the same Accept→GENERATE_THREAT_EVENTS drain
- Keep `EventDuelStarted` as idempotent safety (condition gate in lowerFinesse)
- Shared `applyAuraToParticipants()` for the three entry points

`StatesTrait::stHighDramaChallengeActionResolution` fizzle path: call
`_01089::clearLeftoverDebuffs` — Accept may already have applied, fizzle skips
EventDuelEnd/stDuelEnd; NEXT_PLAYER_EVENTS drains the restore.

## Not changed / intentional
- Accept UI `previewChallengeThreat` still shows unpenalized FIN (preview never
  fires Accept/Intervene — correct per "begins at accept")
- Refuse still no apply
- Floor / Absorbed / swap / clearLeftoverDebuffs behavior unchanged

## Tests
`Card_01089_Test`: Accept stacks ahead of pending GenerateThreat; Intervene
debuffs challenger; DuelStarted after Accept is idempotent. All 27 pass.
