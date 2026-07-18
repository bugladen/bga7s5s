# Pre-commit: attachment Riposte required

## Context
Eddie asked for a new pre-commit check: if a class extends FactionAttachment or CityAttachment, it must have `$this->Riposte =`.

Last session was Syrneth Compass (_03055) — which does set Riposte (and Parry/Thrust). This is likely a scaffolding guard so future attachments don't forget combat lines.

## Decision / WHY
Added as check #11 inside the existing `.githooks/pre-commit` bash script rather than a separate hook file. WHY: the repo has a single pre-commit that loops staged PHP files and runs pattern checks; splitting would duplicate staging/filter plumbing and diverge from how every other rule is enforced.

Only require `$this->Riposte =`, not Parry/Thrust. WHY: Eddie asked only for Riposte. Attachments often set all three together, but the hook should match the stated rule — don't invent Parry/Thrust requirements without being asked.

Regex mirrors existing checks: `class\s+\w+\s+extends\s+(FactionAttachment|CityAttachment)`, then `\$this->Riposte\s*=` so `$this->Riposte = 0` counts. WHY: zero is a valid printed value and must not fail the hook.

Updated CLAUDE.md table so the documented rules stay in sync with the hook (that table was already slightly incomplete vs Maneuver/Technique/etc., but I only added the new row rather than rewriting the whole table).

## Unfinished / notes for next agent
- Hook won't catch attachments that inherit Riposte from a parent without setting it in the subclass ctor — by design; Eddie wants the assignment present in the class file.
- If Eddie later wants Parry/Thrust too, same pattern.
