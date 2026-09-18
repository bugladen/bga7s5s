# Technique_02037 Mysta — challenge + duel arming

## Context
Eddie: Technique works both in-duel and Challenge. Same sibling risk called out in `2026-09-18-02` / fixed today for `Technique_01186`.

## Bug
`EventDuelNewRound` cleared `CancelAdversaryGamble` whenever the owning character became actor. After Challenge activation that NewRound is **round 1 for the challenger** — before the adversary's first round. Flag died; adversary could gamble freely.

Also: no `EventChallengeRejected` clear — Refuse after Resolve would leak the flag into a later duel. Resolve already got `adversaryId` from challenge hub, but no `CHOSEN_TARGET` fallback.

## Fix (mirror Technique_01186)
1. Store `$BlockedAdversaryCharacterId` from Resolve (`$event->adversaryId`, fallback `CHOSEN_TARGET`) — no duel-round helpers on challenge.
2. `$AwaitingAdversaryRound = true` on arm. Flip false on adversary's NewRound (block now active). Clear on owner's NewRound **only if** `!AwaitingAdversaryRound`.
3. `EventChallengeRejected` clears when owner is the challenger.
4. `eventCheck` only throws while `!AwaitingAdversaryRound` (actively in blocked round).

## WHY Awaiting flag
01193 clears on apply; this effect must span the whole adversary round. Awaiting distinguishes "armed, waiting for their NewRound" from "actively blocking this round." Without it, challenge round-1 owner NewRound looks identical to "owner's turn after blocked round."

## Not needed
No challenge/duel chooser states — pure flag + eventCheck. No `EventGenerateChallengeThreat` — deferred gamble denial, not threat. `IN_DUEL` gate already absent on this Technique.
