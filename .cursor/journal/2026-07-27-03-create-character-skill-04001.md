# create-character skill update from _04001

## Why update

Benci Bommarito surfaced two shapes that weren't in the skill:

1. **"Opposed by N+ wounded → +Combat"** — hybrid of Ise flag ±1 and Angeline location-counting, plus a Home trap (`LOCATION_PLAYER_HOME` shared) and wound-event order (`characterHandled`).
2. **Technique look/sink/reorder own Faction Deck** — Action_04cd15 mechanics in a duel Technique with private args. Live bug: forgot `EventHandlers.js` `addSortTagToCard` → no reorder number chips.

Also fixed wiring.md wrong path `Traits.php` → `TraitNames.php`.

## Files touched in skill

- `SKILL.md` — shape table rows
- `pattern-a.md` — "Opposed by N+ wounded"
- `pattern-e.md` — "Look / sink / reorder own Faction Deck"
- `wiring.md` — chooseList EventHandlers section + TraitNames path
- `checklist.md` — item 7 expanded; items 62–64; journal list
- `references.md` — `_04001`, `Technique_04001`, `Action_04cd15`

## Feel

The EventHandlers miss is the one that will burn the next agent hardest — OnEntering/OnUpdate look complete and the reorder UI is silently broken. Calling it out in three places (pattern-e, wiring, checklist) on purpose.
