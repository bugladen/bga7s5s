# Hop on Board — gambling Maneuver not offered after gamble choose

## Report
Eddie: Hop on Board did not offer the gambling maneuver when chosen from revealed gamble cards.

## Context inherited
- `_03069` dual a/b (`b extends a`): plain swap vs Gambling +1 Riposte + swap.
- Aug 23 Harpoon fix: `b` skips activate Harpoon gate; Resolve skips chooser when Harpooned; Riposte still applies.
- Aug 23 Back-empty-buttons: `argsDuelUseManeuverFromCombatCard` needs `buildCity()` for location gates.
- Hub model: gamble always `noManeuver` → apply stats → hub; Maneuver is opt-in.

## Investigation
Traced `actGambleCardChosen` → `DUEL_GAMBLED=true` → `DUEL_PENDING_MANEUVER_CARD` → hub `hasManeuversAvailableToPlayer` / `getManeuversAvailableToPlayer`.

`DUEL_GAMBLED` only cleared at EOR / duel end. Normal/FREE gamble arms pending and should offer `b` if parent's other-character gate passes. Serialize round-trip of `_03069` keeps both a and b with distinct Ids.

## Causes considered
1. **Roll the Bones** — RtB branch sets `DUEL_GAMBLED` but sinks chosen card, never sets `DUEL_PENDING_MANEUVER_CARD`, never offers chosen card's maneuvers. By design (journal `2026-04-29-02`).
2. **Alone at location** — `b::isAvailableToPlayer` called `parent` (a) which requires ≥1 other controlled character. Neither a nor b offered. Conflicts with Harpoon precedent (Riposte without swap when swap illegal). **← confirmed by Eddie.**

## Fix (alone-at-location)
`Maneuver_03069b`:
- `isAvailableToPlayer`: call `Maneuver::isAvailableToPlayer` + `DUEL_GAMBLED` only — do **not** inherit a's "other character at location" gate.
- `EventResolveManeuver`: skip shared 03069 chooser when Harpooned **or** no swap targets; notify accordingly; Riposte still from `EventDuelCalculateManeuverValues`.

WHY: Same shape as Harpoon path already on b — partial effect (Riposte) is legal when swap half cannot run. Queuing the chooser with empty `ids` would stick the player (no Back on that state).

Plain `a` still requires a swap target (correct — swap is its only effect).

## Unfinished
None for this fix. RtB still does not offer maneuvers by design — separate if Eddie wants UX change there.
