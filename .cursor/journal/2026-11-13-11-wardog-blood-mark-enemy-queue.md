# Smuggled Long Rifle — Blood Mark dual arrival Pass-only bug

## Report

Player: Blood Mark moved Maxime + Ravenna into a City adjacent to Wardog host. Two rifle Covert Reaction prompts. Passed on Ravenna (first). Maxime's prompt only had Pass — no Wound button. Wanted Maxime.

## Root cause

`Reaction_05Wardoga` stashed a single `$EnemyId`. Blood Mark queues two `EventCardMoved` in one `runEvents` pass (same as Rosa / 02033 journal). Both fire before the first UI:

1. Ravenna Moved → `EnemyId = Ravenna`, queue RT1
2. Maxime Moved → `EnemyId = Maxime` (overwrite), queue RT2
3. RT1 buttons used overwritten id → "Wound Maxime" (player may not notice name) or whichever order landed last
4. Pass cleared `EnemyId = 0`
5. RT2 → `woundableEnemy(0)` null → Pass only

That matches "first prompt had wound, second only pass" exactly. Order of who "appeared first" is reaction-order chooser / queue order; the wipe on Pass is what kills the second Wound button.

## Fix

FIFO `public array $EnemyIds` like `Reaction_01182` / `Reaction_01181`:

- Append on each qualifying Moved
- Buttons / description use front of queue (description now names the enemy — helps dual-arrival clarity)
- Pass → `array_shift` only (does not `setUsed`)
- Wound → shift, clear rest, `deleteTransitionEvents`, `setUsed` (once-per-day; leftover Pass-only UIs would be dead)

## WHY not "only fire once per ability" like Rosa 02033

Rosa's printed intent was once per Blood Mark. Rifle is "after an enemy moves … Wound them" — each enemy arrival is a separate optional shot until you spend Used. Pass-then-wound-second is the intended play here (user wanted Maxime after skipping Ravenna). Deduping by `abilityId` would wrongly block Maxime's offer entirely.

## Tests

`Card_05Wardog_Test`: dual queue, pass-keeps-second-wound, wound-cancels-second-offer. 18 green.

## Unrelated today

Earlier notes: Monet hover, Volk leave, Sabre thrust Used, Improvising, Vittoria stale flag, Torvo, etc. This is a fresh CAD bug report.
