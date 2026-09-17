# Andriana Dondolo (02001) Reaction fires at Home

## Bug

Sorcerer Reaction ("When target opposing non-Sorcerer intervenes or refuses a challenge • Wound them") offered while Andriana was at Home.

## Cause

`handleEvent` only checked different controller + non-Sorcerer. No co-location / city gate. `isValidTargetForAbility` already had same-location, but that only runs at resolve/validation — the reaction transition was already queued.

## Fix

Both intervene and refuse branches now require:
1. `cardInCity($andriana)` — opposing only exists in city
2. `$character->Location == $andriana->Location`

Mirrored the city check into `isValidTargetForAbility` too. WHY city gate beyond same Location: `LOCATION_PLAYER_HOME` is shared across players, so two Homes can share the same Location string without being "opposing."

## Pattern

Same definition as Reaction_03cd10 / Theah::getOpposingCharactersAtLocation: opposing = different controller at same city location.
