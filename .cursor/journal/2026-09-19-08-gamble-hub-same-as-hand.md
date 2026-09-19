# Gamble vs hand combat card — hub return

## Ask
Eddie: When a player gambles, does the state go straight into whether to use the maneuver? It should follow the same pattern as choosing combat card from hand.

## Answer (current code)
No — already hub-aligned since Aug 11 commit `58e57454` ("Rework of Duel Options").

`actGambleCardChosen` always `nextState("noManeuver")` (never `useManeuver`). Sets `DUEL_PENDING_MANEUVER_CARD`, then:
`DUEL_CHOOSE_GAMBLE_CARD_EVENTS` → `DUEL_APPLY_COMBAT_CARD_STATS` → `DUEL_SET_NEXT_COMBAT_CARD` → `noMoreCombatCards` → `DUEL_CHOOSE_ACTION`.

Same end state as hand play (`actDuelActionChooseCombatCard` → combat card events → apply stats → set next → hub). Maneuver is a hub button, not forced after gamble.

## Stale docs
Skills/journals still mention `useManeuver` → `DUEL_USE_MANEUVER_FROM_COMBAT_CARD` from gamble choose. That transition was removed from `DUEL_CHOOSE_GAMBLE_CARD` in the hub commit. Only leftover named `useManeuver` is under `DUEL_APPLY_COMBAT_CARD_STATS_EVENTS` → `DUEL_RESOLVE_MANEUVER` (nothing queues it today).

## If Studio still goes to maneuver chooser
Deploy/upload lag — local FrameworkActionsTrait already always noManeuver. No code change this session unless Eddie reports otherwise after upload.
