# Hop on Board gambling Maneuver vs Harpoon Riposte

## Context
Prior: `Maneuver_03069a` Harpoon gates (activate + confirm) inherited by `Maneuver_03069b`. Journal `2026-07-20-07` said keep button visible for explanatory UserException — treated both a and b as pure-swap for Harpoon.

## Bug / ask
Eddie: `Maneuver_03069b` must still apply +1 Riposte when actor has `HARPOON_CONDITION`. Activate-time throw on shared parent blocked the whole gambling maneuver before `EventDuelCalculateManeuverValues`.

## Fix (in `Maneuver_03069b` only)
1. Override `eventCheck` → `Maneuver::eventCheck` (skip parent's Harpoon activate gate).
2. On `EventResolveManeuver` when Harpooned: `Maneuver::handleEvent` only — **do not** queue transition `"03069"`. Notify that swap is skipped / Riposte still applies.
3. Riposte calc unchanged.

## WHY skip chooser (not enter + fail on confirm)
`duelResolveManeuver_03069` has no Back transition. Entering the picker while Harpooned would strand the player (01063 got Back for that exact trap). Skipping the transition keeps Activate → Resolve → Calculate pipeline intact so Riposte lands.

## Unchanged
`Maneuver_03069a` still throws on Harpoon at activate (pure swap, no value half). Central `swapParticipantsInDuel` + confirm-time gate remain for any path that still reaches swap.

## Not playtested on Studio
