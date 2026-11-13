# Vittoria Reaction_01014 — stale inPlayThug cancels challenge

## Report
Joern challenged Vittoria; player expected hand-muster, got "new target" for hand Thugs (Angelo). Click → "use … (same location) as the new target" → "is not a valid target" → Challenge cancelled. Thug never entered play.

Card id in report was 03045 (Curious) — challenger is Joern `_03015`. Not the Torvo fix from earlier today.

## WHY (root cause)
Same flag-leak shape as UL `Reaction_01032` (journal 2026-04-01-08):

1. Decline hand-muster with **no** location Thug set `inPlayThug = true` *before* the empty check, then released the event and `setUsed(true)` **without clearing** `inPlayThug`.
2. Dusk only cleared `Used` (`CardReaction`) — phase flags persisted on the serialized reaction.
3. Next day: `CharacterTargeted` set `inHandThug = true` without clearing stale `inPlayThug`.
4. Description/buttons used non-exclusive `if`s → prompt said "new target" while listing hand Thugs.
5. `performReaction` prefers `inPlayThug` → no muster → `BasicChallenge::isValidTargetForAbility` fails (Angelo still in HAND) → `CHALLENGE_CANCELLED`.

Log line "use … (same location) as the new target" is exclusive to the inPlayThug branch — confirms this path.

## Fix
- `clearPhaseFlags` / `beginHandThugPhase` / `beginInPlayThugPhase` / `beginMoveHomePhase` — exactly one phase
- Decline-hand-with-no-location-Thug: clear flags, do **not** set `inPlayThug` first
- Dusk on Reaction_01014 also clears phase flags
- Description + buttons: else-if chain (moveHome → hand → in-play)

## Tests
- `decline hand with no location Thug clears inPlayThug`
- `stale inPlayThug plus hand Thug still musters on challenge target` (dusk clear + beginHand wins over re-poison)

19/19 Reaction_01014 pass.

## Not doing
ChallengeIssued already skips `isValidTargetForAbility`; CharacterTargeted still validates (Torvo Combat gate needs it). Flag exclusivity is enough for this report.
