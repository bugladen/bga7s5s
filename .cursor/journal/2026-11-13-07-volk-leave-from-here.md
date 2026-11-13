# Volk Reaction_05Volk leave-from-here restore

## Report
Volk offered Follow when a non-opposing enemy moved from their Home. They were never at Volk's location.

## Root cause
Earlier playtest (2026-10-14 / 2026-11-06) deliberately widened "opposing" to enemy-controller-while-Volk-in-city and omitted `fromLocation == owner.Location` so city-Volk could follow Home→Forum. That was wrong — "opposing" means co-located.

## Fix
- `Reaction_05Volk`: require leave-from-here again. Keep `cardInCity` (Home has no opposing) and **no** adjacent gate (that's Horatio-only). Dest still any other play site.
- Flipped Home-leave offer test to assert no offer; follow/own/used/discard tests now use leave-from-here origins; added Home dest + arrive-not-leave cases.
- Corrected create-character skill/checklist/pattern-d/helpers/references that taught "Volk is not leave-from-here".

## WHY not keep remote follows
Printed text is "After an opposing character moves". Opponent at their Home is not opposing Volk. Difference vs Horatio is destination freedom (any play site vs adjacent city), not whether they must leave Volk's site.

## Tests
`php tests/run.php tests\regression\cad\Reaction_05Volk_Test.php` — 9/9 PASS.

(Windows: runner only treats `\` as absolute-ish path separator, so forward-slash suite args get double-`tests/`.)

## Follow-up: no Home destination
Playtest: opposing left for Home → prompt offered, but Volk cannot follow Home. Dest gate now `locationInCity` only (handleEvent + performReaction). Flipped Home-dest offer test. Skill docs updated again — Volk = leave-from-here + other city (not Home; not adjacent-only).
