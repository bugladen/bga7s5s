# Shackles equipping to Home

## Bug

Shackles (`_03066`) let players equip onto opposing characters at Home when the equipper also had a higher-Finesse character at Home.

## WHY it happened

`Game::LOCATION_PLAYER_HOME` is **one shared string** for every player. The Finesse gate scans `getCharactersAtLocation($target->Location)` for an ally with greater `ModifiedFinesse`. At Home that scan sees both players' Home characters as co-located, so the "same location opposing" half silently passed without a City check.

Original comment claimed the ally scan also enforced opposing same-location — true for City sites, false for Home.

Legion's Caress (`_01021`) already `cardInCity`s; Shackles did not. HD Equip args/`act` also called `getCharactersAtHome()` (all players) whenever any `CanEquipToOpponents` was in hand, so opposing Home hosts were offered in the UI.

## Fix

1. `_03066` `eventCheck`: `cardInCity` before controller/Finesse checks.
2. `_03066` `canAttachTo`: City location list (no Theah) + opposing controller.
3. `argsHighDramaEquipActionChoosePerformer` + `actHighDramaEquipActionPerformerChosen`: always `getCharactersAtHomeByPlayerId` — own Home still equippable; opposing Home never offered for CanEquipToOpponents.

## Tests

`Card_03066`: Home target with higher-Finesse Home ally throws; `canAttachTo` false for opposing Home.
`ArgumentsTrait_HighDrama`: CanEquipToOpponents includes City opponent + own Home, excludes opposing Home.

## Do not "fix"

Do not treat Home as a valid opposing location for CanEquipToOpponents without printed text that explicitly allows it. Both current cards (Caress, Shackles) are City-only.
