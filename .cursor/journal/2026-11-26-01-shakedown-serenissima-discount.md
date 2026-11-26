# Blackmail discount vs Patricia (05Shakedown / 05Serenissima)

## Context
Last session notes were wealth-cost test sync / cancel-sibling audit / Otto-Kaj-Gustavo drop ack. User report: Blackmail (-1 if performer Scoundrel/Villain) not discounting when Patricia performs.

## Bug
`Action_05Shakedown::getActionFromHandDiscount` called `$performer->hasTrait("Scoundrel")` / `Villain` with **no** `$queryCard`.

Patricia's `hasTrait` (Uwe shape) only invents traits when `$queryCard !== null` + same ControllerId + not blanked. Null query = printed traits only — she is Hero/Academic/…, not Scoundrel/Villain.

## Fix
Pass `$owner` (the Risk) as `$queryCard`:
`$performer->hasTrait("Scoundrel", $owner) || $performer->hasTrait("Villain", $owner)`

WHY owner not action: `hasTrait` signature is `?Card $queryCard`; Action is not a Card. Same-controller gate uses `$queryCard->ControllerId` — Risk in hand shares Patricia's controller. Matches Maneuver_01051 / Action_01036 passing the ability owner.

## Test
`Action_05Shakedown_Test` — Patricia at Docks, assert discount 1. Suite 6 PASS.

## Footgun for later (not fixed this pass)
Same bare `hasTrait` on other E.2 performer-trait discounts: `Action_01171`, `Action_01176`, `Action_04030`/`04059`, etc. Any of those will also miss Patricia until they pass queryCard. create-risk E.2 checklist does not yet say "pass owning card as queryCard" — skill update if user asks / next E.2 card.
