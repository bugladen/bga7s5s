# Bernoulli's Approach (_04007)

## Orientation

Prior session finished Assassin's Garb `_04006` (FactionAttachment). This session: first BAS Risk — Bernoulli's Approach scaffold had Text only.

## Classification

1. **Pattern E:** While adversary has more wounds than participant → -1 combat-card cost via `getManeuverFromCombatCardDiscount`.
2. **Pattern C (×2):** Dual Duelist Maneuvers — split `a`/`b` like `_01108`.
   - a: -3 Thrust • +2 Riposte (pure calc)
   - b: -1 Parry • +2 Thrust (pure calc)

## Design decisions (WHY)

- **Discount only on Maneuver_04007a:** `Card::getManeuverFromCombatCardDiscount` sums every Maneuver on the Risk. Putting the same clause on both a and b would give -2. Mirror single-Maneuver cards (`_03036`, `_01084`) — one carrier is enough.
- **Wounds not Modified stats:** Printed comparison is wounds (`$adversary->Wounds > $actor->Wounds`), not Combat/Finesse. Same field as heal gates / Technique_01013.
- **Pure calc, no Resolve / no states:** Both Maneuvers only mutate `EventDuelCalculateManeuverValues` (incl. negative thrust/parry like `Maneuver_03009`). Framework rolls back on cancel → `EventManeuverCanceled handler not needed`.
- **Bernoulli trait:** Not in TraitNames; added alphabetically between Betrayal and Bodyguard.
- **No IRiskThatTargetsCharacters:** No character chooser / no printed "Target".

## Alternatives considered

- Discount on Risk `handleEvent`: Pattern E says combat-card discounts live on Maneuver at pay time — don't invent Risk-class discount.
- Shared base class for a/b: Distinct effects, same Duelist gate — separate classes clearer (skill dual-a/b discipline). `b extends a` only when Gambling adds calc on shared resolve (`_03069`); not applicable here.

## Shipped

- `_04007` + `IHasManeuvers` / `ManeuverTrait`
- `Maneuver_04007a` (discount + calc), `Maneuver_04007b` (calc)
- Bernoulli in TraitNames
- php -l clean; no states/JS

## Skill update (same day)

Eddie asked to fold `_04007` learnings into `create-risk`:

- Pattern E: wounds predicate table row + **dual-Maneuver discount-once** section (WHY: `Card` sums every Maneuver)
- Pattern C: pure-calc negatives + dual same-trait Duelist a/b note
- SKILL shape table / compose / finish traps / description triggers
- references `_04007`, checklist 28/35/36/50, helpers (`Wounds`, Card sum)

WHY capture in the skill: next agent will happily copy the discount onto both `a` and `b` (or use ModifiedFinesse for a wounds comparison) — the skill exists to stop that regression.