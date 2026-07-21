# Damya Kahina (_03038) — Sea Serpent

## Orientation

Eddie asked to finish `_03038` via create-character. Stub is a Castille Character (not Leader) with two City Actions in Text:

1. Draw a card. Then, discard a card.
2. Your equipped character moves to this location. Then, destroy their attachment to draw cards equal to its printed cost, plus one. (Attachment must be destroyed to draw.)

## Plan / WHY

**Action A (`Action_03038a`)** — Pattern C hand discard after auto-draw.
- Queue `createCardDrawnEvent` in `EventActionTriggered`, then transition to discard picker.
- WHY draw before the discard state: printed order is Draw → Discard; client needs the drawn card in hand for the factionHand picker.
- Availability: `cardInCity` + player will have ≥1 hand card after draw (hand nonempty OR faction deck+discard nonempty). Empty-everything would hang the discard state.
- Hand picker uses `factionHand.setSelectionMode` + `onCardDiscarded` (NOT `highlightCardsAsSelectable`) — skill Pattern C.

**Action B (`Action_03038b`)** — two-step: pick mover → pick attachment to destroy.
- "Equipped character" = your character with ≥1 non-FakeAttachment, not already at Damya's location.
- WHY exclude already-at-location: text says "moves to this location"; same gate as Reaction_03016b style pull-to-here.
- WHY no `IAbilityThatTargetsCharacters`: text has no "target" keyword (skill inverse rule).
- Destroy = unequip + `createCardDiscardedFromPlayEvent` (canonical destroy recipe from Action_01174 / Maneuver_01142), then N draws where N = WealthCost + 1. Capture cost before destroy.
- Parenthetical: destroy is required for the draw — no Pass/skip on attachment step.
- Move with `engage=false` — no Engage printed.
- Attachment step uses button list (Adelheide 01194 pattern), not board highlight.

State IDs: `4030381` (a), `4030382` / `40303822` (b / b_2) — a/b suffix scheme like 01152.

## Done

Implemented both City Actions + full wiring (states, states.inc.php transitions, JS enter/leave/buttons/EventHandlers).

Uncertainties / things Eddie may want to revisit:
- Same-location equipped characters are ineligible (strict reading of "moves to"). If Damya should allow destroy-in-place when already at her location, relax `Location != owner.Location`.
- Damya herself is excluded when at her own location (always) — intentional under the move gate.
- Cost-0 attachments still draw 1 (cost+1) — correct per printed math.

Feelings: Action A is the cleanest draw-then-discard I've wired; Action B's two-step with button attachment picker (01194) felt right vs board-highlighting tiny attachment art. No IAbilityThatTargetsCharacters felt correct but worth Eddie confirming if "Your equipped character" is treated as targeting in house rules.
