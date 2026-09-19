# So It Begins (01183) — Maneuver column note

## Ask
Eddie: when So It Begins reduces Parry, put a note on the duel table. Prefer no new table; mirror cancel-note work; place note in Maneuver column.

## WHY Maneuver column
Cancel notes (`recordCanceledAbilityInDuelTable`) already INSERT display names into `duel_round_maneuver` / `duel_round_technique` — reload-safe, no schema. Combat Card column only stores card ids, so a combat-column note needed either a new table or live-only notify.

## Approach
`Theah::recordDuelRoundColumnNote('maneuver', 'note_{cardId}', 'So It Begins: -1 Parry')`:
1. INSERT into `duel_round_maneuver` (opaque sourceId, not a real maneuver)
2. Notify `duelRoundColumnNote` — live append, no R/P/T (stats stay greyed like cancel)
3. Reload already loads `maneuver_name` via UtilitiesTrait

`_01183` calls it when applying -1 Parry (skip if dashed). Chat explanation unchanged (inject code).

## Deliberate
- Not Combat Card column (no name storage there without schema).
- Generalized helper supports technique mode too for future passives.
- Note may sit in Maneuver column beside a later real Maneuver — same as canceled-then-chosen technique history.
