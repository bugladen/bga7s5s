# Devotion (_01163) end-of-day timing

## Question from Eddie
Does Devotion add itself to hand at end of day? Should be after dusk (dusk = discard to panache).

## Answer
No — Devotion does not return itself. The Action sinks one deck card, puts one in hand immediately, and parks the third facedown at a city location via `_01163_CardClone`. At day end, that hidden original goes to hand (if you controlled the location) or discard (otherwise). Devotion the Risk is already resolved/discarded when played.

## Bug
Clone resolved entirely on `EventDuskPhaseBegin` — before panache discard. So the recovered card counted against hand size and could be discarded at dusk. Wrong for "At the end of the day."

Old journal (2026-03-31-05) rationalized Begin via Yevgeni 01125 — wrong analogy. 01125's text is literally "beginning of Dusk"; Devotion says end of day.

## Fix
Two-step on the clone:
1. `EventDuskPhaseBegin` — snapshot `ControlledLocationAtDuskBegin` (controllers still valid).
2. `EventDuskEndOfDay` — add to hand / discard based on snapshot; clear flag.

WHY snapshot: `stDuskPhaseCleanup` zeroes location Controllers before discard and before EndOfDay. Checking control at EndOfDay alone would always discard.

WHY EndOfDay not PhaseEnd: matches card wording and the framework's "END OF DAY" notify; both are after panache discard.

## Tests
Updated `Card_01163_Test`: begin only snapshots; end-of-day resolves (including after controllers cleared to 0). Action suite unchanged. 14/14 pass.
