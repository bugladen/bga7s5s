# Soline el Gato (01089) — SOLINE_EL_GATO_CONDITION

Mirroring the pattern just applied to 01143. Soline el Gato gives -1 Finesse to adversaries at her location while engaged in a duel. Wired the condition tag to follow the finesse modifier.

## Changes

- **`modules/php/cards/_7s5s/_01089.php`**: in `lowerFinesse`, after queueing the finesse-modified event, `addCondition(SOLINE_EL_GATO_CONDITION)`, persist via `updateCardObjectInDb`, fire `solineElGatoConditionStarted` notif. In `raiseFinesse`, the inverse: `removeCondition` + `solineElGatoConditionEnded` notif.
- **`seventhseacityoffivesails.js`**: added JS constant `this.SOLINE_EL_GATO_CONDITION = 'Soline el Gato Finesse Condition'` matching the PHP `Game::SOLINE_EL_GATO_CONDITION` constant.
- **`modules/js/Notifications.js`**: registered the two notifs and added handlers that mutate `card.conditions` and call `refreshTooltipForCard(card)`. No chip — text-only condition surfaced in the tooltip's Conditions row.

## Why centralize on raise/lower

Both helpers are called from four sites (DuelStarted, DuelEnd, DefenderSwapped, ChallengerSwapped). Putting the addCondition/removeCondition + notif inside `raiseFinesse`/`lowerFinesse` means every call site automatically stays in sync with no per-site duplication. If a future swap path is added, the condition tracking comes free.

## Caveat I'm sitting with

`raiseFinesse` runs at DuelEnd only if the affected character isn't in discard/locker. If a character is destroyed mid-duel, the condition stays on `card.conditions` in the DB but the card is gone from play, so it doesn't matter for tooltips. If destroyed-then-resurrected somehow, the stale condition would reappear with whatever "current" stats — but no resurrection mechanic in 7s5s makes this realistic. Not fixing.
