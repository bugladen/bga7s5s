# Technique_01066 adversary-only-enemy gate

## Context
Eddie: Technique_01066 should ensure there is only 1 opposing character at the location, AND that the character is the adversary.

Prior audit (2026-03-31-02) fixed multiplayer enemy counting (`ControllerId != owner`) but left `count == 1` as the whole gate.

## Why the Id check matters
`getDuelRoundOpponent()` can return a locker/deserialized last-known adversary. That copy still has a Location. Counting enemies at that Location with `count == 1` can pass on some *other* opposing character who happens to be alone there — the dead adversary is not on the board, so they were never in the filtered list. The printed condition is "the adversary is the only enemy", not "exactly one enemy exists at the adversary's last known location."

## Change
`isAvailableToPlayer`: null-check adversary; require `count($enemyCharacters) == 1` **and** `reset($enemyCharacters)->Id == $adversary->Id`.

Left location source as `$adversary->Location` (same as March audit). Did not switch to `$owner->Location` ("this location") — out of scope for this ask; duel participants are normally co-located.

## Unrelated unfinished
2026-09-13-02 Maneuver_01084 locker discount still paused awaiting Eddie clarification.
