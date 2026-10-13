# Move Along (01056) — restore challenge Techniques

## Symptom
Eddie: Joern issued a challenge via Move Along; Langschwert Technique was not offered, so no +1 Thrust.

## Cause
May 2026 restructure (`2026-05-02-02-move-along-01056-restructure.md`) fired `EventChallengeIssued` manually on target pick, then sent both defender branches to `CHECK_CANCELLED` — intentionally **skipping** `TECHNIQUE_AVAILABLE`. Documented as an acceptable single-card trade-off; it was not.

## Fix / WHY
Journal option #2 from that restructure note:

1. **Target pick** → set MOVE_ALONG globals, clear `CHOSEN_TECHNIQUE`, transition `01056_3` → `TECHNIQUE_AVAILABLE` (challenger). No manual ChallengeIssued.
2. Standard path: technique activate/pass → `stIssueChallenge` (ChallengeIssued **with** technique id) → SETUP → RESOLVE_TECHNIQUE → `CHECK_CANCELLED`.
3. **`CHECK_CANCELLED`**: if MOVE_ALONG and live → `moveAlongChoice` → `01056_2` (defender move Home / continue).
4. Move Home → cancelled + `01056_4` → CHECK_CANCELLED cleanup. Continue → `01056_5` → ACCEPT directly (so CHECK_CANCELLED does not re-offer move-home).

Also block `actBack` from activate-technique when MOVE_ALONG (same class of wrong-Back as Valeri — would land on generic CHOOSE_TARGET).

## Ordering vs card text
"Issues a challenge … They may move Home … cancel." Technique is part of issuing; flee comes after ChallengeIssued + ResolveTechnique. You're Embarrassing Yourself / other cancel-on-issue still abort before move-home. Langschwert threat is GenerateChallengeThreat (post-Accept) so flee after choosing Technique correctly drops the +1.

## Do not regress
- Henri / Turais / "when issues a challenge" still fire on move-home (ChallengeIssued before 01056_2).
- Continue must not loop move-home (01056_5, not back through CHECK_CANCELLED notCancelled).
- No double ChallengeIssued.

## Stuck on 01056_2 (follow-up)
Eddie: buttons did nothing. Cause: `actFromCardWithId` uses `TRANSITION_INTERNAL_ID`; technique activate (and challenge reactions) overwrite it before `moveAlongChoice`. Card lookup no-ops → no `nextState`. Fix: in `stChallengeActionCheckCancelled` before `moveAlongChoice`, restore SOURCE/INTERNAL from `CHOSEN_ACTION` via `getInPlayActionById` (Risk is in Purgatory; still in buildCity).

## Tests
`Action_01056_Test` updated for technique-available transition + 01056_4/5 keys.
