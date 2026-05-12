# Bastien Swap (01063) — Stuck DUEL_CHALLENGER on Rejection

## Bug

User found an in-play character with a stray `DUEL_CHALLENGER` condition. After
auditing every add/remove site for that condition (challenge issued, duel end,
challenge rejected, check-cancelled cleanup, swap participants in duel, and
`Technique_01063Swap`), the suspect path turned out to be:

- Non-musketeer X (at Bastien's location, so granted `Technique_01063Swap` via
  Bastien's aura) issues a challenge.
- X activates the Swap technique with Bastien as the swap target.
- Defender **refuses** the challenge.
- `DUEL_CHALLENGER` ends up stuck on Bastien.

## Why

`HIGH_DRAMA_CHALLENGE_ACTION_ACCEPT_CHALLENGE` has only one transition
(`"" => HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT` in `states.inc.php:1250`).
This is intentional — rejection still wounds the target by `CHALLENGER_THREAT`,
so `stHighDramaChallengeActionGenerateThreat` must run.

But that means `EventGenerateChallengeThreat` fires **even on rejection**, and
`Technique_01063Swap`'s handler for that event was unconditionally mutating
card state.

Event ordering on the rejection path:

1. `actHighDramaChallengeActionReject` queues `EventChallengeRejected(performer=X, ...)`,
   sets `CHALLENGE_ACCEPTED=false`, transitions to `GENERATE_THREAT`.
2. `stHighDramaChallengeActionGenerateThreat` queues `EventGenerateChallengeThreat`.
3. Events run FIFO:
   - `EventChallengeRejected` — Hub removes `DUEL_CHALLENGER` from X. ✓
   - `EventGenerateChallengeThreat` — X's `Technique_01063Swap` matches by id;
     handler runs `owner.removeCondition` (no-op, already gone) then
     `newChallenger.addCondition(DUEL_CHALLENGER)` on **Bastien**. ✗
4. `stHighDramaChallengeActionResolution` routes "rejected" → cleanup → no duel ever
   starts → `EventDuelEnd` never fires → Bastien keeps the condition forever.

The swap also bogusly fires a `ChallengerSwappedEvent` notification, so the
user-visible log on a rejected challenge would have shown a "challenger swapped"
message even though no duel happened.

## Fix

Guard the `EventGenerateChallengeThreat` branch of `Technique_01063Swap` with a
`CHALLENGE_ACCEPTED` check. On rejection there's no duel coming, so the swap
shouldn't transfer the condition (or fire the swapped event).

Left a `WHY:` comment in the code explaining the rejection-flow rationale so a
future session doesn't "simplify" the guard away.

## Considered alternatives

- **Skip `GENERATE_THREAT` on rejection in the state machine.** Wrong — the
  threat is needed to compute the rejection wound.
- **Move `EventChallengeRejected` processing after `EventGenerateChallengeThreat`.**
  Would fix this one symptom but breaks the invariant that rejection cleanup
  happens before any threat-related side effects; risks cascading bugs in other
  techniques.
- **Have the cleanup path re-remove `DUEL_CHALLENGER` from `CHOSEN_PERFORMER`
  after rejection.** Patching the symptom downstream of a buggy swap rather than
  preventing the buggy swap. Also `CHOSEN_PERFORMER` has already been overwritten
  to Bastien by the time the rejection-resolution state runs, so this would
  actually work, but it's a fix at the wrong level.

The technique-level guard is the right level because the swap itself is what's
semantically wrong on rejection.

## Not investigated further

The other `EventGenerateChallengeThreat` handlers (Technique_01067, 01049, 01011,
01123, 01157, 01196, 02002, generic Plus*Thrust, GainLethal, DestroyPlusOneThrust)
appear to only tweak threat numbers, which are harmless when the challenge is
rejected (the rejection-wound math just uses whatever they computed). 01063Swap
was the only one mutating persistent card state in this handler. Did not audit
each one in depth — worth a closer look if another stuck-condition case turns up.
