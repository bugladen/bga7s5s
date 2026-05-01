# Contempt and Hatred (01143) - Add CONTEMPT_AND_HATRED_CONDITION tracking

## What changed

Per user request, when 01143 reduces a Mercenary's Influence by 1, also add `Game::CONTEMPT_AND_HATRED_CONDITION` to the character. When the influence is restored (locker), remove the condition.

Modified `_01143.php` in three places:
1. `EventResolveScheme` — addCondition on each mercenary in play
2. `EventCardSentToLocker` — removeCondition on each mercenary in play
3. `EventCharacterRecruited` — addCondition when a Mercenary is recruited while scheme is in play

After each add/remove, call `$event->theah->game->updateCardObjectInDb($character)` to persist the Conditions array (per pattern in `_01125.php`).

## WHY

Conditions on a card are visible in tooltips/UI (see journal `2026-04-29-08-conditions-on-tooltips.md`). The user wants players to see *why* a mercenary's Influence is reduced. The influence-modified event already adjusts the stat, but doesn't expose the reason — the condition tag does.

## Pattern reference

`addCondition` mutates in-memory only; must call `updateCardObjectInDb` to persist (BGA rebuilds state per request). Same lesson as `2026-04-13-13-boars-guile-01125-audit.md`.

## Follow-up: dedicated condition notification

Initially I tried bolting `refreshTooltipForCard` onto the three stat-modified notifs (combat/finesse/influence). User reverted that — the right shape is a dedicated condition notification, mirroring `indomitableWillConditionStarted/Ended`.

Final wiring:
- **PHP `_01143.php`**: after each `addCondition`/`removeCondition`, fires `contemptAndHatredConditionStarted` / `contemptAndHatredConditionEnded` notif with `cardId`. Three call sites (resolve, locker, recruit).
- **JS `seventhseacityoffivesails.js`**: added `this.CONTEMPT_AND_HATRED_CONDITION = 'Influence Reduced by Contempt and Hatred'` constant alongside the others.
- **JS `Notifications.js`**: registered the two notifs and added `notif_contemptAndHatredConditionStarted` / `Ended` handlers. They mutate `card.conditions` and call `refreshTooltipForCard(card)`. No chip is placed on the card — this condition is text-only (visible in the tooltip's Conditions row), unlike Indomitable Will / Adversary which have visual chip badges.

WHY no chip: the user only asked for the condition to be tracked so the tooltip reflects *why* the influence is reduced. Mercenaries can be many on the table at once and each has the influence number visibly modified already; another chip would be visual noise.

WHY a dedicated notif over piggybacking on `characterInfluenceModified`: conditions and stat changes have different lifecycles (a condition might be added without changing a stat, e.g. for marker conditions). Keeping them separate avoids overloading the influence notif's contract.
