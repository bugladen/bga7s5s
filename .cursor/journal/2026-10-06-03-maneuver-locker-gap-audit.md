# Audit: maneuvers with 01135-style post-locker deferred gaps

Asked after Miyato+Mireli fix: which other maneuvers need the same treatment?

## Gap definition
Miyato/Ota (02043a): original Risk → Locker at EndOfRound (out of buildCity);
clone removed at NewRound. Effects that must still fire *after* that
window and live only on the Risk/clone instance are broken.

EndOfRound → global (02039, 03023) is fine: fires while card still in line,
payload in globals.

## Needs same class of fix

| Card | Maneuver | Shape | Miyato-reachable? |
|---|---|---|---|
| Master of Valroux Style `_01084` | `Maneuver_01084` | Exact twin of 01135: arm on Resolve, apply on next-round `EventDuelCalculateCombatCardStats` via `$IncreaseAdversaryThrust` | No (Montaigne) — still broken if lockered by anything else |
| Borets `_01129` | `Maneuver_01129` | `$IsActive` eventCheck bans Maneuvers/Techniques for rest of duel | **Yes (Ussura)** — locker + NewRound clone remove ends the ban early |

## Same structural gap, not in Miyato deck

| Card | Maneuver | Notes |
|---|---|---|
| Proper Drama `_03047` | `03047a` / `03047b` | Adversary next-round gamble choose/block on instance fields. Castille. |

## Not the same gap (checked)

- `02039`, `03023` — EndOfRound → PENDING_*_THREAT globals (Miyato design case)
- `01031`, `01059`, `01164`, `01107`, `01052`, `01103` — EndOfRound fire while still in line
- `01051` — same-round wound redirect; Eisen
- `01165` — different: techniques live on Character; Risk holds cleanup list. Miyato may leave orphaned technique cleanup (or miss cleanup), not a missing deferred combat-stat apply
- `01082` / `03022` Final Strike — CharacterDestroyed listener; locker loses the arm (Montaigne/Eisen)

## Recommendation
1. **01084** — same Unravel/01135 global+EventHub shape if we care beyond Miyato
2. **01129** — highest Miyato priority: persist lock off the Risk (global or Character-side flag) through DuelEnd
