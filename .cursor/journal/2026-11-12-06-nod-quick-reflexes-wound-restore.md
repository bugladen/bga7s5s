# Night of Drinking vs Quick Reflexes — Relentless wound lost

## Report
Duel ended → Relentless (02060b) → own participant took wound → opponent Quick Reflexes to ignore Sanjay's wound → Night of Drinking cancelled QR (chat said so) → Sanjay never took the Relentless wound.

## Root cause
`Reaction_02059` uses the Stubborn-style cancel-and-hold pattern: on `EventCharacterBeingWounded` it sets `$event->canceled = true` and clones into `savedWoundEvent` *before* the player pays.

Accept path (EventRiskReactionTriggered): clears the hold, wound stays canceled.
Decline path: re-queues the clone + `skipNextEvent`.

Night of Drinking deletes `EventRiskReactionTriggered` for the cancelled Risk. That skips Accept, so the hold is never cleared and Decline never runs. Chat correctly says "cancel" but the wound stays canceled forever.

Same class of bug as Stubborn (`_01140`) / Unyielding Loyalty (`_01032`) — those already have `revertCancellation` wired in 01109 / 01109v2. QR was missing from that list.

## Fix
1. `Reaction_02059::revertCancellation` — re-queue held wound + `skipNextEvent` (same as Decline).
2. `Reaction_01109` + `Reaction_01109v2` — when cancelled risk is `_02059`, call `revertCancellation`.

WHY hardcode like Stubborn/UL rather than a generic interface: established NoD pattern; `ICancelReaction` is only a marker for stack-vs-queue pay speed, not restore. Don't invent a new contract for one more edge case.

## Tests
- `Reaction_02059`: revertCancellation re-queues once, skip prevents re-hold, empty is noop.
- `Reaction_01109` / `01109v2`: cancel of QR after RiskPlayed offer restores Sanjay's wound and deletes QR's pending Triggered.

## Unfinished
None for this report. Attachment twins (Spaulders 04053, Cooper, Gary) are not Risk Reactions so NoD cannot cancel them — no restore needed there.
