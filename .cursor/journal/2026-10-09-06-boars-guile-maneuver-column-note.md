# Boar's Guile (01125) — Maneuver column note for +1 Thrust

## Ask
User: Boar's Guile should note in Maneuver column that the combat card gained a bonus. Remembered another ability that does this (So It Begins). Clarified card is **01125**, not Yevgeni 01116.

## Prior art
**So It Begins (_01183)** — `recordDuelRoundColumnNote('maneuver', 'note_{id}', '…')` when applying -1 Parry. Technique_01204 uses Technique column for -2 Parry.

WHY Maneuver column: Combat Card column only stores card ids; maneuver/technique tables already persist display names (cancel-note path). Reload-safe, no schema.

## Change
`_01125::handleEvent` after `addThrust(1)` when adversary has `ADVERSARY_OF_YEVGENI`: if `!$event->dashedThrust`, record `"The Boar's Guile: +1 Thrust to combat card"`. Chat explanation unchanged (inject code). Skip dashed — bonus doesn't apply, note would lie.

## Also Yevgeni (_01116)
User asked: same pattern? Yes — passive +1 Thrust on his combat cards via `EventDuelCalculateCombatCardStats`. Same note: `"Yevgeni: +1 Thrust to combat card"` when `!$dashedThrust`.

First pass had put it on 01116 by mistake (thought "Boar's Guild" = The Boar); reverted; then user confirmed Guile + asked for Yevgeni too. Both stay.

## Deploy
`_01125.php`, `_01116.php`.

## Audit: other cards with the same gap
User asked who else needs the note. Pattern = modifies combat-card R/P/T on `EventDuelCalculateCombatCardStats` with no Maneuver/Technique name already in that round's columns.

**Already noted:** 01183 So It Begins, 01125 Guile, 01116 Yevgeni, Technique_01204 (-2 Parry next round).

**Same gap (passive / deferred, no column home yet):**
- `_01043` Uwe — +1 Thrust vs Sorcerer
- `_01122` Torsten — +1 Thrust at 2+ wounds
- `_03037` Sanjay — gambled +1 Riposte
- `_01121` Ren — adversary combat cards -1 Parry
- `_01195` Eager Blade — destroy for +1 Riposte
- `Action_04009` — Duelist first combat card +1 Riposte
- `Technique_01193` — adversary next -1 Thrust (twin of 01204 which already notes)
- `Maneuver_01135` — adversary next -2 Thrust
- `Maneuver_01084` — adversary next +1 Thrust
- `Reaction_02017` — announce → -1 Riposte on that combat card

**Not the gap:** Lethal-only (02033/04041), escape/side effects (01169/01053/01085), same-round Maneuver/Technique calc (already named in column), gamble-count (Technique_01101).

**Follow-up:** All gap cards (+ Unravel in EventHub) wired in `2026-10-09-07-combat-card-column-notes-gap.md`.
