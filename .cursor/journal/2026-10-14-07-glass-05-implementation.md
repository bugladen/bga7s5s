# _05Glass Unseat — Risk (CAD test)

## Printed text

1. **City Action:** Pressure your performer's location with [Finesse]. If successful, this location becomes uncontrolled.
2. **Maneuver:** If the adversary has more [Influence] than your participant • Wound them.

Test CAD card — no `clienttranslate` / `_()` (user request; same as Forsythe/Overcoat/Volk).

## Classification

| Clause | Pattern |
|---|---|
| City Action pressure Finesse → unclaim on success | A.8 pressure→success payoff shape, mirror `_01141` Strong Hands but STAT_FINESSE + unclaim (`createLocationBecomesUncontrolledEvent`) not claim |
| Maneuver Influence > → wound adversary | Pure-resolve C (`Maneuver_03033` wound; comparison inverted — adversary has *more* Influence → `$adversary->ModifiedInfluence > $actor->ModifiedInfluence`) |

## WHY decisions

- **No custom states/JS:** `_01141` goes straight to `pressureLocation` via `createTransitionEvent(..., "pressureLocation", $this->Id)`; result on `EventLocationPressureResult`. Same for Glass.
- **Do not gate Action on location controlled / canBecomeUncontrolled:** Same discipline as A.8 / `_01141` claimability — pressure still plays; unclaim is emit-only with notify-and-skip when already uncontrolled or Indomitable Will. Check `getControllerForLocation != 0` before unclaim (C.10 / `Maneuver_04048b`).
- **Finesse performer filter:** `canPressure(STAT_FINESSE)` like Strong Hands Combat — excludes dashed Finesse.
- **Maneuver "If" = availability filter** (not silent resolve no-op): Influence `>` on Modified stats; also gate `!characterIsInDiscardOrLocker` because wound is the only payoff.
- **No Gambling gate:** print has plain Maneuver, not Gambling.
- **Overthrow trait** missing from TraitNames — add alphabetically.
- **CardNumber = 12** kept from scaffold; no HD state needed so no States.php collision worry.

## Files

- `_05Glass.php` — IHasActions + IHasManeuvers wire
- `actions/Action_05Glass.php` — RiskCityAction pressure Finesse → unclaim on success
- `maneuvers/Maneuver_05Glass.php` — pure-resolve wound when adversary Influence >
- `TraitNames.php` — added `Overthrow`

## Done

Implemented. `php -l` clean. No custom states/JS (shared pressure pipeline). No regression suite for CAD Glass yet.

## Skill fold-in

Updated `.claude/skills/create-risk/`:

- **Pattern A.17** (pattern-a.md) — fixed-stat pressure → claim (`_01141`) or unclaim (`_05Glass`); no Leader/wound/choose-stat/locker; controller check before unclaim.
- **pattern-c** — inverted "adversary has more X" compare; `Maneuver_05Glass` as non-Gambling pure-resolve sibling of `03033`.
- SKILL shape table + compose + Finish traps; checklist 74/75; references Strong Hands + Unseat; wiring CAD plain-string + A.17 notes.

WHY A.17 separate from A.8: Ambitious is a composition; Strong Hands / Glass are the bare pressure→control-change skeleton agents kept inventing as one-offs.
