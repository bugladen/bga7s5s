# Boar's Guile (01125) — available merc offered as enemy

## Bug
User: Tijani sat as an available mercenary; during Boar's Guile "choose an enemy character" she was selectable. She should not be — available mercs are uncontrolled.

## Cause
`planningPhaseResolveSchemes_01125_4` JS scanned **all** `cardProperties` for `controllerId != 0 && != activePlayer`, with **no** `isCardInPlay` check. Out-of-play enemy rows (locker/discard, `divId = null`) still matched. Then:

```js
dojo.query('._7sfs-card', card.divId)[0]
```

with null/missing root searches the whole document and returns the **first** `._7sfs-card` — often an available city merc (Tijani). `makeCardSelectable` highlighted her even though her `controllerId` is 0.

Server already rejected ControllerId === 0 on confirm (hardening journal `2026-06-07-01`), so this was a UI false-positive, but Pass was also disabled when those stale matches inflated `count`.

## WHY this fix shape
Not just "add isCardInPlay to the client loop":
1. Same fallthrough risk remains if divId is stale/wrong.
2. Modern choosers already take **server `characterIds`**.
3. Rules: enemy = `isNotControlledByPlayer` (controlled + not you). `getCharactersInPlay()` already requires `isControlled()` + city/home — available mercs never appear.

## What changed
- `_01125::argsFromCard` state `_4`: `characterIds` from in-play `isNotControlledByPlayer`.
- `states.7s5s.php` `_4`: `argsEmpty` → `argsForState`.
- Entering JS: `highlightCardsAsSelectable(characterIds)` only; leave via `unhighlightCards`.
- Confirm validation: `isNotControlledByPlayer` + in city/home (not bare ControllerId !== 0).

## Do not regress
Do **not** go back to scanning `cardProperties` with `dojo.query('._7sfs-card', card.divId)` for this chooser. Null-root query is the foot-gun.
