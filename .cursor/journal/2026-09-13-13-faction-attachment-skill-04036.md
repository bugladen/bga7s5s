# Faction-attachment skill update from _04036

Captured Ciphered Tome lessons so the next dual City Action / Renown relocate does not invent a source picker or put Home in a "more Renown" list.

## What went in
- Shape table: two Actions; "from this location" renown relocate; "more Renown" dest filter
- Pattern C: a/b naming, two GameStates, 01007 is a FROM picker (Aldo, not a dest picker), Home has no Renown track, do not write states.7s5s.php for GameState-class HD states
- compose / references / helpers / wiring / checklist 16c–16e

## WHY Home exception is explicit
Pattern C already said "include Home when the filter can match." _04036b is the first attachment where text says "a location" but the filter (more Renown) cannot match Home. Without the example, a future pass will "fix" it by adding Home + makeHomeEndcapMarkerSelectable and getCityLocation(HOME) will throw.

## WHY not copy 01007
Aldo picks the source location. Ciphered Tome source is fixed (host location). Same three-event batch, different picker.

Caught myself writing "Cesca" for 01007 — it's Aldo Bussotti. Fixed before leaving the skill.
