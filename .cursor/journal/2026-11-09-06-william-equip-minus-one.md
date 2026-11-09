# _05William planning equip at -1 cost

## Context
v5 card text: "They may equip an available Artifact there, at -1 cost." Original implementation (2026-10-13) followed older "paying all costs" wording — no scheme discount.

## Gap
`getEquippableArtifactsAtLocation` / pay via `PAY_STATE_EQUIP_ATTACHMENT` only used world `getEquipDiscount`. No William +1. A WealthCost-2 Artifact with 1 hand Wealth looked unaffordable and paid full price.

## Fix
- `PlanningEquipDiscountActive` flag on the scheme; `getEquipDiscount` adds +1 for controlled Artifact while armed (Kaj shape, resolve-scoped).
- Arm before affordability check after Academic move; disarm when no Artifact, on Pass, after equip, and on zombie leave of pick/pay states.
- WHY flag not standing aura: scheme stays at Home all day — without a gate every HD Artifact equip would get −1.
- WHY arm before `getEquippableArtifactsAtLocation`: that helper calls `Theah::getEquipDiscount`, which must see the scheme's +1 for listing.
- State 3 prompt: "at -1 cost" instead of "paying all costs".

## Tests
`tests/regression/cad/Card_05William_Test.php` — discount arm/disarm + affordability of cost-2 at 1 Wealth. 6/6 PASS.

## Follow-up (empty hand, cost-1 Artifact)
User: moved Academic onto a 1-cost available Artifact on turn 1 with empty hand — should equip free after -1, didn't.

Gaps / fixes:
1. Affordability only saw the -1 when `PlanningEquipDiscountActive` was already armed. Location-chosen check now goes through `getPlanningEquipDiscount`, which adds the printed -1 when the flag is still off (and skips the extra +1 when the flag is on so pay does not double-count).
2. `cost > 0 && handWealth < cost` — cost 0 is always affordable (empty hand on turn-1 resolve).

Tried skipping the pay UI when cost ≤ 0 (Gary free path). User asked to keep the normal pay flow even at cost 0 — reverted that; Confirm with empty selection is valid via `isValidWealthPayment(0, 0, …)`.

Tests: cost-1 at 0 Wealth included; cost-2 at 0 Wealth still excluded; cost-2 at 1 Wealth while disarmed included. 7/7 PASS.

## Not done
create-scheme skill still says "paying all costs" for William row — left alone (user didn't ask for skill edits). Worth updating next time that skill is touched.
