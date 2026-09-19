# duelUseManeuverFromCombatCard — always show Back

## Report
Eddie: There should be a back button from `duelUseManeuverFromCombatCard`.

## What was there
JS already had Back, but gated: `if (!gambled && !abnormalFlow)`. Server already has `actBack` + transition `"back" => DUEL_CHOOSE_ACTION`. Decline was removed earlier (Aug hub work); that journal kept the gambled/abnormal hide as "pre-existing."

## WHY remove the gate
Under hub model, Maneuver is opt-in from `duelChooseAction` (same as Technique). Technique always shows Back. Hiding Back after Gamble or Broken Time (`ABNORMAL_FLOW`) committed the player to picking a Maneuver once they opened the chooser — no return to hub for Technique / End Round without using Maneuver.

Back still does **not** clear `DUEL_PENDING_MANEUVER_CARD` (Decline used to; that was wrong for hub). Pending Maneuver stays available at hub.

## Change
`OnUpdateActionButtons.js`: always add Back for this state. No PHP/states change needed.

## Still hide? Not for now
Pay-for-maneuver still branches gambled/abnormal to `actBackWithTransition('backAbnormalFlow')` but both transitions land on the same use-maneuver state anyway — leave alone unless Eddie asks.

## Follow-up: who sets ABNORMAL_FLOW for this state?
Eddie asked. Only **Broken-Time (`Maneuver_01077`)** sets it on the duel combat-card pipeline (with `NEXT_COMBAT_CARD`). Other setters are High Drama pay/engage/copy (01106, 01124, 01154, 01133, 01008, 01116b, 03060, 03013, 02011) — not this state.

Caveat: `stResetDuelAction` on `duelChooseAction` **deletes** ABNORMAL_FLOW. Under hub, Broken-Time's second card lands back on the hub before Maneuver is opened, so `args._private.abnormalFlow` is usually **false** on `duelUseManeuverFromCombatCard` even after 01077. The old hide-Back gate was mostly dead for this state; gambled was the live half.

## Follow-up: does anything else use `gambled` for use-maneuver state?
Eddie asked. **No.** Stripped `gambled` and `abnormalFlow` from `argsDuelUseManeuverFromCombatCard`. `DUEL_GAMBLED` global unchanged (Gambling Technique/Maneuver gates, stats). Pay state still has its own args + Back branch.

## Verify
- Normal combat card → Maneuver → Back → hub; Maneuver button still there
- Gambled card → Maneuver → Back → hub; can pick Technique
- Broken Time second card → Maneuver → Back → hub (if that path is wrong for Technique on auto-played card, gate techniquesAvailable separately — do not re-hide Maneuver Back)
