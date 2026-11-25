# Kaj Action_01180 — drop multi-ack reveal state

## Ask
User: "Clean up Kaj" after Gustavo/Otto ack removals.

## Prior flow
`01180` MULTI (full ack) → `01180_2` GAME `stSetCurrentPlayer` → `01180_3` ACTIVE choose Artifact → `01180_4` performer → `01180_5` pay.

## New flow
Trigger → log reveal → `01180` ACTIVE choose Artifact → `_4` performer → `_5` pay. Deleted `_2`/`_3` classes + constants. Kept `_4`/`_5`. Hub `"01180"` / `"01180_4"` unchanged.

## Side effect / fix
`_4` already had `"back" => HIGH_DRAMA_PLAYER_TURN_01180`. Under the old layout that jumped back to MULTI ack (broken UX). After collapse, Back correctly returns to choose Artifact. Did not need to retarget the transition.

## Log
Added `cards[]` to the Artifact-count reveal notify (was inject-codes-only — Monet note). Action-used notify left as-is.

## JS
Merged former `_3` enter/leave/buttons/EventHandlers onto `highDramaPhase01180`. `PlayerActions` pass map: `_3` → `01180`.

## Tests
`Action_01180_Test` + `Card_01180_Test` — trigger asserts cards[]; state constants drop `_2`/`_3`.
