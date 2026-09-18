# Technique_01186 Maryam — challenge + duel arming

## Context
Eddie already removed `IN_DUEL` from `isAvailableToPlayer` (57938495 this morning) so the Technique appears on Challenge Activate. User asked to ensure it **works** during Challenge (not just menu visibility). Same sibling risk called out in `2026-09-18-02-technique-01193-challenge.md`.

## Bug
`EventDuelNewRound` cleared `CancelOpponentManeuvers` whenever Maryam became actor. After Challenge activation that NewRound is **round 1 for the challenger** — before the adversary's first round. Flag died; adversary could use Maneuvers freely.

Also: no `EventChallengeRejected` clear — Refuse after Resolve would leak the flag into a later duel.

## Fix (mirror Technique_01193 deferred-arm shape, adapted for round-spanning block)
1. Store `$AdversaryId` from Resolve (`$event->adversaryId`, fallback `CHOSEN_TARGET`) — no `getDuelRoundOpponent` on challenge.
2. `$AwaitingAdversaryRound = true` on arm. Flip false on adversary's NewRound (block now active for that round). Clear on owner's NewRound **only if** `!AwaitingAdversaryRound`.
3. `EventChallengeRejected` clears when owner is the challenger.
4. `eventCheck` only throws while `!AwaitingAdversaryRound` (actively in blocked round). Still uses `EventResolveManeuver.adversaryId == owner` — that event has no `actorId`.

## WHY Awaiting flag (vs 01193 clear-on-apply)
01193 applies once on combat-card calc then clears. 01186 must span the whole adversary round, so we can't clear on first apply. Awaiting distinguishes "armed, waiting for their NewRound" from "actively blocking this round."

## Not needed
No extra challenge/duel states — effect is pure flag + eventCheck, no player chooser. No `EventGenerateChallengeThreat` — effect is deferred Maneuver denial, not threat.
