# Edeline Reaction_01037 Home gate

## Report
Duel Edeline vs Valeri (Leader). Edeline plays Hexenjagd as combat card → Forced sends her Home engaged. After the duel, her Reaction offered moving Valeri onto Home. Should not offer when she is at Home.

## Root cause
`getAdjacentCityLocations(PLAYER_HOME, false)` returns the home-adjacent city sites (Docks/Forum/Bazaar…). That is correct for the **movement graph**, but wrong for this printed reaction ("engaged participant at an adjacent location → move them to this location"), which is city-board adjacency.

Hexenjagd queues the Home move on `EventDuelEndOfRound`; by `EventDuelEnd` Edeline is already Home. Reaction_01037 then saw Valeri still at the duel city site as "adjacent" and offered the pull onto `LOCATION_PLAYER_HOME`.

## Fix
Gate offer + `isValidTargetForAbility` on `$theah->cardInCity($owner)`. WHY not change `getAdjacentCityLocations`: Home→city adjacency is intentional for many cards (Philip, Volk, etc.). Only this reaction's "pull to my location after duel" must not treat Home as a board space with adjacent duel leftovers.

## Tests
`Reaction_01037_Test`: new case Edeline at Home + engaged defender at Forum → no queued transition. 5/5 pass.

## Unfinished
None for this report.
