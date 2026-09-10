# Rosine Friese (_01041) — Sorcerer aura dead after home

## Context inherited
Prior notes: 2026-03-30 audit (count-transition bugs on muster/destroy/recruit) and 2026-05-07 dusk false-+1 from shared `"Player Home"` string. Dusk fix: short-circuit `getOpposingSorcererCount(home)=0` + early-return when `$this->Location == home`.

## Bug
User: "Sorcerer aura is not working." After dusk Rosine loses the +1 correctly (city→home self-move), then never gets it back when she moves from home into a city with an opposing Sorcerer.

## Root cause
Self-move handler was gated `fromLocation != LOCATION_PLAYER_HOME`. March audit claimed that was fine because muster uses `EventCharacterMustered`. That misses High Drama moves out of home — the normal post-dusk path every day.

Sequence:
1. Dusk: city→home, fromCount≥1 / toCount=0 → −1. Correct.
2. Next day: home→city with opposing Sorcerer. Self-move skipped (from=home). Early-return then fires because Location is still home (`runEventHubAfterCards=true`). Aura never reapplies.

Muster-into-city still worked; only leave-home moves were broken. Feels like "aura never works" in normal multi-day play.

## Fix
Dropped the `fromLocation != home` gate on self-move. Home count short-circuit makes from=home safe (fromCount always 0; +1 only when toLocation has ≥1 opposing Sorcerer). Early-return after self-move kept — still needed for dusk Order A (other characters' home traffic falsely matching Rosine.Location once she's home).

## Why not rewrite to Angeline (_03026) absolute recompute
Angeline's model is more robust, but Rosine's transition deltas are correct once home→city is included. Minimal change preserves the dusk guards that were deliberately added. Don't "clean up" into absolute recompute without a reason — easy to regress the shared-home dusk case.

## Did not touch
- Other-character arrive/leave / muster / destroy / recruit handlers
- Action_01041 City Action
- getOpposingSorcererCount home short-circuit
