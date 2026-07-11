# Valroux Exemplar (_03036) Implementation

## Context

Eddie asked to finish `_03036` via create-risk. Card renamed mid-session from stub "Loyal" to **Valroux Exemplar**. Prior today: Loyal `_03035`.

## Card Text

Passive: If your participant has more [Finesse] than the adversary, this card has -1 cost.

**Duelist Maneuver:** +1[Riposte] for each other card in your dueling line. If you have three or more other cards in your dueling line, the adversary discards a card.

## Approach / WHY

### Cost discount → `getManeuverFromCombatCardDiscount` on Maneuver (Pattern E via 01084 shape)

Same channel as Master of Valroux (`Maneuver_01084`). WHY on the Maneuver not the Risk class: discount applies when this card is played as a combat-card maneuver. Gate: `ModifiedFinesse >` adversary (literal "more than").

### Riposte scaling → pure calc like `Maneuver_01166`

`getCardObjectsAtLocation(LOCATION_DUELING_LINE, ControllerId)`, unset this card's Id, `+= count`. Fires on `EventDuelCalculateManeuverValues`. Only push explanation when count > 0 (avoid "adds 0 Riposte" noise).

### Conditional adversary discard → `EventResolveManeuver` + `Maneuver_01115` sub-state

Only when `count(other cards) >= 3`. Also skip transition if adversary hand empty (avoid stuck chooser — 01108a gates availability on hand; we gate at resolve because the discard is conditional on line size, not on whether the maneuver itself is offered).

Duelist gate in `isAvailableToPlayer`. No ManeuverCanceled state (no sticky flags). State id `52503036`, name `duelResolveManeuver_03036`.

## Files touched

- `modules/php/cards/faf/_03036.php` — IHasManeuvers + Maneuver_03036
- `modules/php/cards/faf/maneuvers/Maneuver_03036.php` (new)
- `modules/php/States/faf/State_duelResolveManeuver_03036.php` (new)
- `modules/php/States.php` — DUEL_RESOLVE_MANEUVER_03036
- `states.inc.php` — transition under DUEL_RESOLVE_MANEUVER_EVENTS
- `modules/js/On{Entering,UpdateActionButtons,Leaving}State.faf.js` — hand select discard UI
- `modules/js/EventHandlers.js` — enable Confirm when hand card selected

## Feelings

Clean composition of three existing exemplars (01084 discount + 01166 line count + 01115 discard). Two cards named Loyal in a row was confusing until Eddie renamed this one — good catch. Empty-hand skip at resolve is the right call here; putting it on isAvailable would incorrectly hide the whole maneuver when the player still wants the Riposte scaling.

## Line-ending footgun

Write tool produced blank line between every statement in `_03036.php` (doubled CRLFs). Eddie caught it. Rewrote via PowerShell with explicit CRLF normalization matching sibling faf cards. Don't use Write on existing CRLF PHP files without verifying — prefer Shell rewrite or StrReplace when touching Windows-CRLF files.
