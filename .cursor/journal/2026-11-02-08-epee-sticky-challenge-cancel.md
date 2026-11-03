# Épée Sanglante — challenge cancelled immediately after choose

## Report
gregfilip used Épée Sanglante Issue Fight Challenge. Log (stack = reverse chrono):
announce → chosen to Challenge + Combat stat → Action resolved → Challenge was cancelled.
No "is issuing a challenge" / ChallengeIssued line. Went straight from choose to cancel.

## Diagnosis
Same sticky `CHALLENGE_CANCELLED` shape as Sanjay/Vittoria (2026-10-29-02), different root.

Log order matches `skipChallengeBeforeTechnique` exactly: ActionResolved (runEvents) then `challengeCancelled` notify. That gate fires when the flag is true (or performerId===0) *before* `stIssueChallenge`. AnnouncePendingChallenge already logged the choose line from the framework target pick — so the pick succeeded; technique available then aborted.

`stChallengeActionCheckCancelled`'s cancelled branch used the flag but never deleted it, then transitioned to NEXT_PLAYER. `stNextPlayer` / `stHighDramaPlayerTurn` / `stHighDramaPhase` also left it alone. Only `skipChallengeBeforeTechnique` and `stDuelEnd` cleared it (and `stIssueChallenge` resets false — never reached).

So any prior cancel that went through CHECK_CANCELLED (YEY, Maryam vs Defending Honor, Spine/Censure after issue, UL after issue, etc.) left true. Next challenge of any type — including Épée's framework pick → TECHNIQUE_AVAILABLE — died with the generic cancel. Extra actions never even hit a player-turn reset.

Not Épée-specific. Épée just uses the shared pick + technique gate, so it shows the bug cleanly.

## Fix
- `stChallengeActionCheckCancelled` cancelled branch: `delete(CHALLENGE_CANCELLED)` after handling.
- `stNextPlayer`, `stHighDramaPlayerTurn`, `stHighDramaPhase`: same delete with the other challenge globals.

WHY all three: checkCancelled is the real consume; next-player covers leftover from other paths / extra actions; player-turn / phase reset is belt-and-suspenders with CHALLENGE_TYPE reset.

## Tests
StatesTrait_Challenge: cancel-flag cleanup asserts flag consumed; Maryam asserts consumed (no manual delete before second call); new case — checkCancelled then stTechniqueAvailable issues. StatesTrait_HighDrama dirty/assert + stNextPlayer clear list include the flag. 73/73.

## Opinion
The Sanjay journal fixed who *arms* the flag. This closes who *disarms* it after a handled cancel. Half the hygiene was done; the CHECK_CANCELLED path was still a landmine. Maryam test even asserted the flag stayed true then manually deleted — that was documenting the bug as expected behavior.
