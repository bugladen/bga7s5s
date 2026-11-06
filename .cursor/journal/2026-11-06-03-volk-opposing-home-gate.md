# Volk Reaction_05Volk Home / opposing gate

## Report
Volk at Home offered Follow after an opposing character moved. Should not — "opposing character" is city-scoped; Home has no opposing characters.

## Root cause
Earlier playtest widened Volk past Horatio's leave-from-here, and the implementation used `cardInPlay` (city **or** Home). That was wrong on the Home side. "opposing" = enemy controller **while Owner is in the city**, not while at Home. Same rule as Axelle `Reaction_04022` / Edeline city-scope notes.

## Fix
- `handleEvent` + `performReaction`: `cardInCity($owner)` instead of `cardInPlay`.
- Kept non-co-located follow (city-Volk + opponent Home→Forum) — that was the real playtest intent vs Horatio.
- Flipped Home-offer / follow-from-Home tests; updated Pattern D / SKILL / checklist so future sessions don't reintroduce `cardInPlay`.

## WHY not leave-from-here
Still omit `fromLocation == owner.Location`. That blocked city-Volk when an opponent left *their* Home. User complaint was specifically Home-Volk firing, not remote follows while in city.

## Tests
`php tests/run.php tests/regression/cad/Reaction_05Volk_Test.php` — 8/8 PASS.
