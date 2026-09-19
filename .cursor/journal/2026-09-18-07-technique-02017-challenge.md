# Technique_02017 Panzerhand — challenge + duel arming

## Context
Eddie: Technique works both in-duel and Challenge. Same sibling risk as `Technique_01186` / `Technique_02037` fixed earlier today.

## Bug
Old code set `NoTechniquesThisRound` immediately on Resolve and blocked **all** `EventTechniqueActivated` while the flag was set. Cleared on `EventDuelEndOfRound` when ending player ≠ owner.

Problems for Challenge:
1. Flag armed before the duel — challenger's round-1 Technique activations would also be blocked (Awaiting needed).
2. No `EventChallengeRejected` clear — Refuse after Resolve leaked the flag into a later duel.
3. No stored `AdversaryId` — challenge has no duel opponent helpers; blocking should target the Resolve adversary only.

EndOfRound clear happened to survive challenge round-1 better than the NewRound premature-clear siblings, but the "block everyone while armed" eventCheck did not.

## Fix (mirror Technique_01186 / 02037)
1. Store `$AdversaryId` from Resolve (`$event->adversaryId`, fallback `CHOSEN_TARGET`).
2. `$AwaitingAdversaryRound = true` on arm. Flip false on adversary's NewRound (block now active). Clear on owner's NewRound **only if** `!AwaitingAdversaryRound`.
3. `EventChallengeRejected` clears when owner is the challenger.
4. `EventDuelEnd` clears leftover state.
5. `eventCheck` only throws while `!AwaitingAdversaryRound` and `$event->ownerId == AdversaryId` (duel activate passes actor as ownerId).

## WHY Awaiting flag
Effect must span the whole adversary round. Awaiting distinguishes "armed, waiting for their NewRound" from "actively blocking this round." Without it, challenge round-1 owner NewRound / mid-round technique choose looks wrong.

## Not needed
No challenge/duel chooser states — pure flag + eventCheck. `IN_DUEL` gate already absent on base Technique / this card.
