# Master of Valroux (01084) vs dead adversary

## Report
Takama (01199) died round 3. Round 4 Montaigne played Master of Valroux Style (01084):
1. Got -1 cost discount (should not — adversary dead, can't be Engaged in play)
2. Riposte bounced threat onto absent Takama (should discard)

## Root causes

### Discount
`Maneuver_01084::getManeuverFromCombatCardDiscount` used `$theah->getDuelRoundOpponent()->Engaged`.

`getDuelRoundOpponent()` when opponent is in discard/locker returns **last-known** `actor_serialized` from prior round — still has `Engaged=true` from when they were alive. That helper is for duel *stats* (Combat etc.), not live board state like Engaged/Location.

Destroyed characters are recreated fresh (`EventCharacterDestroyed`) with Location=Locker and Engaged=false. Live `getCharacterById` is the correct source for Engaged.

### Riposte bounce
`DB::updateRoundWithCombatStats` always did `actorThreat -= riposte; adversaryThreat += riposte`. No absent check. Same last-known Location pitfall would also make EOR location-nullify miss (last-known still shows city), so bounced threat stuck on the dead side's pool.

## Fixes
1. **Maneuver_01084** — discount gates on live adversary: `getCharacterById` + `!characterIsInDiscardOrLocker` + `Engaged`.
2. **DB::updateRoundWithCombatStats** — compute `$adversaryAbsent` from live character once; all three modes (maneuver/technique/combat) only add **Riposte and Thrust** to adversary threat when present. Riposte still reduces actor threat (bounce discarded). Thrust is discarded entirely when absent.

## Follow-up (Eddie confirmed)
Thrust must also not land on an absent adversary — same `$adversaryAbsent` gate as Riposte.

## WHY not change getDuelRoundOpponent
Last-known is intentional for effects that need the dead adversary's duel stats. Changing it globally would break EOR messaging / wound sourcing that rely on that snapshot. Callers that need board state must use live id lookup (pattern already in Maneuver_01079, Technique_01039).

## Left alone
- EOR location check still uses getDuelRoundOpponent last-known Location — related smell, separate from this report.
- Call sites that do `characterIsInDiscardOrLocker(getDuelRoundOpponent())` are wrong for the same reason (last-known Location is city). e.g. Maneuver_03072.

## Feel
Classic "helper returns convenient ghost" trap. Discount bug was entirely 01084. Riposte/Thrust-to-absent was framework-wide and **always present** — not a recent regression. Blame shows unconditional `+= $riposte` / thrust from the combat-stats rework (`41d551b6`, Jan 2026) and earlier duel rework; July's thrust clamp (`921a9ce1`) only stopped negative threat and did not touch absent handling. `$adversaryAbsent` is new with this fix.
