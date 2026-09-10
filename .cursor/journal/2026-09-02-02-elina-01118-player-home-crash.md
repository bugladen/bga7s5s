## Elina 01118 — Player Home renown crash

Fatal: `Undefined array key "Player Home"` in EventHub line 1194 during `EventRenownAddedToLocation`, stack from `Reaction_01118::performReaction`.

**Root cause:** Reaction moves renown to `$elina->Location`. Player Home is a valid character location but NOT a city location — it isn't in `$theah->cityLocations`. EventHub always does `$this->cityLocations[$event->location]->Renown += ...` for renown events.

**How it triggers:** `handleEvent` already gates on `cardInCity($elina)`, so Elina must be in the city when the sorcerer ability fires. But the reaction is interactive — she can move Home (or be moved) before the player picks a source location. `performReaction` then queues `createRenownAddedToLocationEvent(..., $elina->Location, ...)` with `"Player Home"` → crash.

Same class of bug as Odette `_01062`, which explicitly checks `$odette->Location != Game::LOCATION_PLAYER_HOME` in both trigger and button building.

**Fix (Reaction_01118 only):**
1. `getReactionButtonProperties` — if Elina not in city, offer Pass only.
2. `performReaction` — re-check `cardInCity($elina)` before queuing renown events; skip silently if she left the city (race between queue and choice).

Did NOT patch EventHub — Player Home renown in globals would still be wrong; fix belongs at the card. EventHub guard would hide future card bugs.

**Feeling:** Obvious once you remember the shared `"Player Home"` string pattern from Rosine/Nazem journals. Interactive reactions need perform-time location checks, not just trigger-time.
