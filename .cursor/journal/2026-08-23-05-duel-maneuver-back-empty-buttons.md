# Empty maneuver buttons after Back from pay

## Symptom
Eddie: Back from `duelPayForManeuverFromCombatCard` → `duelUseManeuverFromCombatCard` shows no Maneuver buttons (only Back / empty list).

## Context
Follows Hop on Board (_03069) Harpoon work. That card's maneuvers gate on other characters at the duel location (`getOtherCharactersAtDuelLocation` → `getCharactersAtLocationByPlayerId`).

## Root cause
`argsDuelUseManeuverFromCombatCard` never called `buildCity()`. `getCharactersAtLocationByPlayerId` only walks `$theah->cards`, which is empty until build.

First entry often worked by accident: `actDuelActionChooseManeuver` builds the city in the same request before `nextState`, so args saw a populated city. `actBack` does not build; page refresh also would not. Location-gated maneuvers then all return false → empty `maneuvers` array.

WHY not every card: base `Maneuver::isAvailableToPlayer` is `return true` and does not touch Theah. Always-available maneuvers would still list after Back. Location/trait gates (03069a/b, 03035, 01108*, etc.) fail.

## Fix
`$this->theah->buildCity()` at the top of `argsDuelUseManeuverFromCombatCard`, same pattern as `argsChooseDuelTechnique` / `argsChooseDuelAction`. Args must be self-sufficient for Back + refresh.

## Not changing
`argsDuelPayForManeuverFromCombatCard` — no Theah lookups today. Leave alone.

## Verify
WealthCost combat card with location-gated Maneuver (Hop on Board): choose Maneuver → pay → Back → both eligible buttons return. Refresh on use-maneuver state also lists them.
