# Technique_01204 Syrneth Hand — challenge arming

## Context
Eddie: Technique works in-duel and Challenge. Same sibling risk called out in
2026-09-18-02 (01193 Burnished Cuirass challenge fix).

## Bug
Identical to 01193: `EventDuelNewRound` cleared `ReduceAdversaryParry` when the
owning character became actor. After Challenge activation that NewRound is
**round 1 of the duel for the challenger** — before the adversary's first combat
card. Flag died; `-2 Parry` never applied.

Also: Resolve never stored the challenge target. Relied on
`owner == event.adversaryId` at combat-card calc time.

## Fix (mirror Technique_01193)
1. Store `$event->adversaryId` on Resolve (fallback `CHOSEN_TARGET`).
2. Apply `removeParry(2)` when `event.actorId == stored AdversaryId`.
3. **Removed** owner-actor NewRound clear.
4. Clear on `EventChallengeRejected` so Refuse does not leak the flag.
5. Kept wound-on-resolve (01204-specific; 01193 has no wound cost).

## Not changed
`isAvailableToPlayer` — already inherits base `true` (IN_DUEL gate was already
gone; April audit note about duel-only gating is stale).

## Follow-up: Technique column note
Eddie: put a note in the Technique column on the adversary round when Parry
is reduced. Mirror So It Begins (`_01183`) via
`recordDuelRoundColumnNote('technique', 'note_{techniqueId}', 'Syrneth Hand: -2 Parry to combat card')`.
Skip if dashed Parry. Stats still land in Combat Card column via
`removeParry(2)` — note is display-only for the Technique cell.
