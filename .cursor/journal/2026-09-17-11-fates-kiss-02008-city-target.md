# Fate's Kiss (02008) — opposing city targets only

## Bug / request

Action_02008 should only target opposing characters in the city (not Home).

## What was already correct

`getArgsFromAction` for state `02008_2` already filtered:
`cardInCity($character) && isNotControlledByPlayer(...)`

So the highlight UI was already city-only.

## Gaps

1. **`isValidTargetForAbility`** — only rejected own characters; no city gate. Resolve/redirect/API paths could accept Home targets.
2. **`isAvailableToPlayer`** — no check that any opposing city character exists; action could start (pick risk) with zero legal targets.

## Fix

Mirrored Action_02001 / Andriana opposing-home pattern:
- Availability: require ≥1 opposing character with `cardInCity`
- Validation: reject if `!cardInCity`

WHY city gate beyond "different controller": `LOCATION_PLAYER_HOME` is shared across players, so Home characters aren't "opposing in the city."
