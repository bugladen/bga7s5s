# Duel Maneuver/Technique column row borders

User: black border + rounded corners around each explanation text row in Maneuvers, then same for Technique.

## Approach
CSS-only on `#duel_table td[id$="_maneuver"] > p/div` and `td[id$="_technique"] > p/div`.

WHY CSS not a JS class:
- Live path uses `<p>` (`notif_updateRoundWithCombatStats`, cancel, column notes)
- Reload path uses `<div>` (`formatDuelAbilityNameMarkup`)
- Same selector covers both without touching Notifications/Utilities
- `id$="_maneuver"` / `id$="_technique"` scopes to those columns only (not `_*_stats`)

## Values
- `1px solid black` — matches `#duel_table` border weight (acting-character highlight is 2px gold; different purpose)
- `border-radius: 8px` — same as acting-character / stylesheet habit
- Small padding + vertical margin so stacked rows don’t look glued

## Not styled
- Bare "Not Chosen" text (no child element) — intentional; only explanation rows
