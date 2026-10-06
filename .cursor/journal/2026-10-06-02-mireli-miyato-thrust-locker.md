# Mireli's Revision (01135) −2 Thrust lost after Miyato locker

## Report
Miyato/Ota played Mireli's Revision, chose wound + −2 Thrust next round, then
copied via Technique_02043a (combat card → Locker). Adversary never got −2 Thrust.

## Root cause
Deferred −2 lived only on `Maneuver_01135` instance fields (`IsActive` +
`ReduceThrustNextRound`), applied from `EventDuelCalculateCombatCardStats` on
the Risk.

Miyato path kills both carriers before that event:
1. Original Risk → Locker at EndOfRound. `buildCity` deliberately omits locker
   (schemes/auras must not keep firing). Instance never sees next-round calc.
2. Clone on Miyato is `removeClonedManeuver`'d at `EventDuelNewRound` — before
   adversary `CalculateCombatCardStats`. Journal `2026-05-22` already flagged
   this as a known limitation; user hit it in play.

Do **not** fix by loading locker into `buildCity`.

## Fix (Unravel `_04010` pattern)
Arm into `Game::MIRELIS_REVISION_PENDING_THRUST_REDUCTIONS` when choosing wound.
Apply from `EventHub` on `EventDuelCalculateCombatCardStats` (hub always runs;
`runEventHubAfterCards` already true for that event).

Expire when that adversary finishes their round (`stDuelEndOfRound` →
`expirePendingForActor`). Cancel → `clearPendingForManeuver`. Duel end →
`clearAllPending` (Maneuver handler + `stDuelEnd` safety-net — locker cards miss
`EventDuelEnd`).

Removed instance `CalculateCombatCardStats` apply so in-line Risk does not
double-hit with the hub.

Stacking: original arm + clone arm → two entries → −4 if both chose wound
("Copy the effects"). Correct.

## WHY global not "keep clone longer"
Keeping the clone past NewRound only fixes the copy's arm. Original's arm still
dies with the locker'd Risk. Global covers both without touching buildCity.

## Files
- `modules/php/cards/_7s5s/maneuvers/Maneuver_01135.php`
- `modules/php/Game.php` (const)
- `modules/php/theah/EventHub.php` (apply)
- `modules/php/StatesTrait.php` (expire + stDuelEnd clear)

## Not in scope
`Maneuver_01084` (+1 Thrust next round on Risk) has the same locker fragility
if Miyato copies it. Same global/hub shape if it shows up in play.
