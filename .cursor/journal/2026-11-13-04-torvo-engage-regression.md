# Torvo Action_02034 — target should not Engage

## Question
Does Action_02034 cause the target (opponent who may issue the challenge) to Engage? User: if so, it should not.

## Answer
Yes it did, via `stIssueChallenge` auto-engage — not via Action_02034 itself. Accept sets `CHOSEN_PERFORMER` = target, `CHALLENGE_TYPE` = `TORVO_ESPADA`, then issue path engaged the performer.

## History / WHY it was wrong again
- Apr 2026: Torvo added to auto-engage list (challenger = accepting opponent).
- Aug 20 `6f7c85f8`: removed Torvo ("Action no longer forces adversary to engage"). Correct — City Action has no Engage; "They may issue a Combat challenge" is not an Engage cost.
- Aug 22 merge conflict journal `2026-08-22-01`: bas kept TORVO on the list, undoing Aug 20. Skills/docs kept listing Torvo as on-list.
- Sep 19 journal assumed Torvo was still off-list (safe for engaged acceptors). Code had drifted back on.

## Fix
Remove `TORVO_ESPADA_CHALLENGE_TYPE` from `stIssueChallenge` engage condition. Move it into the "does not engage" regression bucket. WHY comment in StatesTrait so the next merge does not put him back.

Card text still blocks intervene via the custom type; only the engage side-effect goes away.
