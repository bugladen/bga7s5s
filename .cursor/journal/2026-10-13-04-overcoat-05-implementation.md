# _05Overcoat Varvara Kurakina — Character implementation

## Printed text

1. **Covert** — `[Influence] pressures at this location cannot succeed.`
2. **Covert Action:** Engage Varvara • Pressure this location with Resolve. If successful, either move an adjacent Renown to this location or claim it.

Test card — no `clienttranslate` / `_()` (CAD prototype, same as Dabney).

## Classification

| Clause | Pattern |
|---|---|
| Covert Influence cannot succeed | Pattern A — EventPressureOccuring flag + force fail in `pressureLocation` (Valeri flag shape, fail instead of +N) |
| Covert Action Engage + Resolve pressure + claim-or-move-renown | Pattern C pressure (no win-ties) + Soline-style mandatory "or" choice |

## WHY decisions

- **Uncontrolled gate on both Covert passive and Covert Action:** Covert keyword parenthetical (Valeri). Printed Action lacks the Reminder text but "Covert Action" is a Covert ability.
- **Dedicated `OVERCOAT_PRESSURE_TYPE` (262144):** set only when `STAT_INFLUENCE` is in `$event->pressureTypes`. Force `[false, …]` at end of `pressureLocation`. Do **not** reuse VALERI (aura +2) or win-ties flags.
- **Resolve pressure Action needs no dedicated type:** no "succeed even if tied" — NORMAL + `PRESSURE_STAT = STAT_RESOLVE`.
- **Choice UX:** Claim button (`id: 0`) + adjacent-Renown location picker (01007 / Odette move recipe). No Pass — mandatory "either … or …" (Soline).
- **Availability payoff gate:** uncontrolled + not Engaged + canPressure(Resolve) + (canClaim OR adjacent Renown > 0).
- **Magistrate** novel trait → TraitNames.
- State id `405009`, transition `"05Overcoat"`, JS in `*.cad.js`, plain strings.

## Files

- `_05Overcoat.php` — Covert handleEvent + Actions wire
- `actions/Action_05Overcoat.php`
- `States/cad/State_highDramaPhase05Overcoat.php`
- Game.php constants, UtilitiesTrait fail branch, States.php, states.inc.php transition, TraitNames, cad JS

## Done

Implemented as planned. No `clienttranslate` / `_()` on this card (test CAD). `php -l` clean on touched PHP.

Choice state supports both `actFromCardWithId` (Claim) and `actFromCardWithLocations` (adjacent Renown) — hybrid of Soline + Aldo 01007.

Unfinished / watch: no regression suite for CAD Overcoat yet (CAD not in `tests/regression`). Playtest Covert fail on basic Influence claim at her uncontrolled site, and that her own Resolve Action still succeeds.

## Skill fold-in (same day)

Updated create-character skill with Overcoat learnings:
- SKILL shape table: Covert Influence-cannot-succeed row + Covert Action claim/move-renown row
- pattern-a: "Covert Influence pressures cannot succeed" (vs Valeri +N); EventPressureOccuring row notes `pressureTypes`
- pattern-c: "Covert Action: claim or move adjacent Renown" (uncontrolled, NORMAL pressure, hybrid Claim+locations PossibleActions)
- checklist 101a + 109; CAD id note 103 extended for Overcoat / plain-string prototype
- references + helpers Covert-fail note

WHY keep Valeri and Overcoat as separate patterns: same EventPressureOccuring flag machinery, opposite `pressureLocation` outcomes (+N vs force fail) and opposite chip/refresh needs.
