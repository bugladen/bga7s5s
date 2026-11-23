# _05Keelhaul Surprise Boarding

Finishing CAD Risk via create-risk. User: no clienttranslate / translate / _().

## Orientation
Prior today: Montoya first-player Maneuver discount + Flourish Riposte; Shakedown Covert B.2.

## Text → shapes
1. **Covert Pirate Action:** Your performer issues a [Combat] challenge to target opposing.
   - Covert = RiskAction + cardInCity + uncontrolled (not RiskCityAction) — Shakedown/Teens.
   - Pirate = heading trait gate on performer.
   - No Engage printed → Sanjay trichotomy (c): mint CHALLENGE_TYPE off auto-engage; engaged Pirates stay eligible (Courageous/Arrogant).
   - Cesca yes (printed "target").
2. **Covert Action:** If you aren't the first player • Finesse challenge to target opposing.
   - Same Covert gate; bullet-If = availability (01103b / Lorenzo OVERRIDE).
   - `IAbilityThatDependsOnNotBeingFirstPlayer` so Lorenzo can offer.
   - DashedFinesse gate (Celerity); Combat half gates DashedCombat (Hulk).

## WHY
- Split a/b Actions (dual distinct headings) — not one mode class.
- Two challenge types (36/37) even though both are never-engage correlators — do not reuse Arrogant/Courageous ids (wrong card identity if anything keys type later).
- No custom GameState — shared choose-target like 05Hulk / 03058.
- Covert is mechanical (uncontrolled City), not a City Action base class — same trap as Teens/Shakedown.
- Engaged performers stay eligible — print has no Engage; do not copy Teens' !Engaged cost gate.

## Shipped
- `_05Keelhaul` + `Action_05Keelhaula` + `Action_05Keelhaulb`
- `KEELHAUL_PIRATE_CHALLENGE_TYPE=36` / `KEELHAUL_CHALLENGE_TYPE=37` in Game.php / FakeGame / JS
- Wire `"05Keelhaula"` / `"05Keelhaulb"` → choose-target under EVENTS
- Skill references + pattern-b Covert-challenge compose note
- Tests: `Action_05Keelhaula_Test` (5) + `Action_05Keelhaulb_Test` (5) — all pass

## Unfinished / watch
- Did not add Cesca allow-list (CAD prototypes skip until asked).
- Framework "does not engage" sample list does not include 36/37 — whitelist means they stay off automatically; optional to expand the sample later.
